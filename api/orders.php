<?php
require_once __DIR__ . '/../config.php';

$pdo = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper generator kode tracking TRX-XXXX
function generateTrackingCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = 'TRX-';
    for ($i = 0; $i < 4; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

// 1. DAFTAR ORDER LELANG TERBUKA (UNTUK RADAR PWA SOPIR)
if ($action === 'auction' || ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($action))) {
    try {
        $stmt = $pdo->query("
            SELECT o.*, c1.name AS origin_city, c2.name AS destination_city
            FROM orders o
            LEFT JOIN routes r ON o.route_id = r.id
            LEFT JOIN cities c1 ON r.origin_city_id = c1.id
            LEFT JOIN cities c2 ON r.destination_city_id = c2.id
            WHERE o.status = 'AUCTION'
            ORDER BY o.created_at DESC
        ");
        $orders = $stmt->fetchAll();
        sendJson(['success' => true, 'data' => $orders]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 2. BUAT PESANAN BARU (DARI FORM BOOKING WEB)
if ($action === 'create' || ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === '')) {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $custName = trim($input['customer_name'] ?? '');
    $custPhone = trim($input['customer_phone'] ?? '');
    $pickup = trim($input['pickup_address'] ?? '');
    $dest = trim($input['destination_address'] ?? '');
    $pickupTime = $input['pickup_datetime'] ?? date('Y-m-d H:i:s', strtotime('+1 day 08:00'));
    $carType = $input['car_type'] ?? 'Toyota Innova Reborn';
    $routeId = intval($input['route_id'] ?? 1);
    $totalFare = floatval($input['total_fare'] ?? 750000);
    $commission = $totalFare * 0.10; // 10% hak admin
    $trackingCode = generateTrackingCode();

    if (empty($pickup) || empty($dest) || empty($custName) || empty($custPhone)) {
        sendJson(['success' => false, 'message' => 'Semua kolom wajib diisi'], 400);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                tracking_code, route_id, customer_name, customer_phone,
                pickup_address, destination_address, pickup_datetime,
                car_type, total_fare, admin_commission, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'AUCTION')
        ");
        $stmt->execute([
            $trackingCode, $routeId, $custName, $custPhone,
            $pickup, $dest, $pickupTime, $carType, $totalFare, $commission
        ]);
        $orderId = $pdo->lastInsertId();

        sendJson([
            'success' => true,
            'message' => 'Pesanan berhasil diterbitkan ke lelang sopir!',
            'order' => [
                'id' => $orderId,
                'tracking_code' => $trackingCode,
                'total_fare' => $totalFare,
                'status' => 'AUCTION'
            ]
        ]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 3. AMBIL ORDER (ATOMIC LOCK: SIAPA CEPAT DIA DAPAT)
if ($action === 'accept') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $orderId = intval($input['order_id'] ?? 0);
    $driverId = intval($input['driver_id'] ?? 0);

    if ($orderId <= 0 || $driverId <= 0) {
        sendJson(['success' => false, 'message' => 'Parameter order_id dan driver_id wajib ada'], 400);
    }

    try {
        // Query Atomic Lock: Hanya berhasil jika status masih AUCTION dan driver_id masih NULL
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET driver_id = ?, status = 'ACCEPTED', driver_assigned_at = NOW() 
            WHERE id = ? AND status = 'AUCTION' AND driver_id IS NULL
        ");
        $stmt->execute([$driverId, $orderId]);

        if ($stmt->rowCount() === 1) {
            // Menang lelang! Ambil data pesanan
            $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch();

            sendJson([
                'success' => true,
                'message' => 'Selamat! Anda memenangkan lelang order ini.',
                'data' => $order
            ]);
        } else {
            sendJson([
                'success' => false,
                'message' => 'Maaf, order ini baru saja diambil oleh rekan sopir lain!'
            ], 409);
        }
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 4. UPDATE STATUS PERJALANAN (MENUJU JEMPUT, TIBA, JALAN, SELESAI)
if ($action === 'status') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $orderId = intval($input['order_id'] ?? 0);
    $driverId = intval($input['driver_id'] ?? 0);
    $newStatus = trim($input['status'] ?? '');

    $valid = ['ON_THE_WAY', 'ARRIVED', 'IN_TRIP', 'COMPLETED', 'CANCELLED'];
    if (!in_array($newStatus, $valid)) {
        sendJson(['success' => false, 'message' => 'Status tidak valid'], 400);
    }

    try {
        $sql = "UPDATE orders SET status = ? WHERE id = ? AND driver_id = ?";
        if ($newStatus === 'COMPLETED') {
            $sql = "UPDATE orders SET status = ?, completed_at = NOW() WHERE id = ? AND driver_id = ?";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newStatus, $orderId, $driverId]);

        sendJson(['success' => true, 'message' => "Status diperbarui menjadi {$newStatus}"]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 5. KONFIRMASI SETOR KOMISI PASCA-BAYAR (DENGAN UPLOAD STRUK)
if ($action === 'settle') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $driverId = intval($_POST['driver_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $bankSender = trim($_POST['bank_sender'] ?? 'BCA');
    $senderName = trim($_POST['sender_name'] ?? 'Mitra Sopir');

    $proofPath = null;
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
        $filename = 'struk-' . time() . '-' . rand(100, 999) . '.' . $ext;
        if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $uploadDir . $filename)) {
            $proofPath = '/uploads/' . $filename;
        }
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO commission_settlements (
                order_id, driver_id, amount, bank_sender, sender_name, proof_image, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'WAITING_CONFIRMATION')
            ON DUPLICATE KEY UPDATE
                amount = VALUES(amount),
                bank_sender = VALUES(bank_sender),
                sender_name = VALUES(sender_name),
                proof_image = COALESCE(VALUES(proof_image), proof_image),
                status = 'WAITING_CONFIRMATION'
        ");
        $stmt->execute([$orderId, $driverId, $amount, $bankSender, $senderName, $proofPath]);

        sendJson(['success' => true, 'message' => 'Konfirmasi transfer komisi berhasil dikirim ke Admin!']);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 6. TRACKING INFO PELANGGAN + LIVE GPS SOPIR (UNTUK LEAFLET MAP)
if ($action === 'track') {
    $code = trim($_GET['code'] ?? '');
    if (empty($code)) {
        sendJson(['success' => false, 'message' => 'Kode tracking wajib ada'], 400);
    }

    try {
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   d.name AS driver_name, d.phone AS driver_phone,
                   d.car_model, d.car_plate, d.car_color, d.rating AS driver_rating,
                   d.current_lat, d.current_lng, d.last_location_update
            FROM orders o
            LEFT JOIN drivers d ON o.driver_id = d.id
            WHERE o.tracking_code = ?
        ");
        $stmt->execute([$code]);
        $order = $stmt->fetch();

        if (!$order) {
            sendJson(['success' => false, 'message' => 'Pesanan tidak ditemukan'], 404);
        }

        // Sensor nomor HP untuk menjaga privasi
        if (!empty($order['customer_phone'])) {
            $phone = $order['customer_phone'];
            $order['customer_phone_masked'] = substr($phone, 0, 4) . '-****-' . substr($phone, -4);
        }

        sendJson(['success' => true, 'data' => $order]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// Default fallback
sendJson(['success' => false, 'message' => 'Action tidak dikenali'], 404);
