<?php
require_once __DIR__ . '/../config.php';

$pdo = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. LOGIN MITRA SOPIR (NOMOR WA + PIN)
if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $phone = trim($input['phone'] ?? '');
    $pin = trim($input['pin'] ?? '');

    try {
        $stmt = $pdo->prepare("SELECT * FROM drivers WHERE phone = ?");
        $stmt->execute([$phone]);
        $driver = $stmt->fetch();

        if (!$driver) {
            sendJson(['success' => false, 'message' => 'Nomor WhatsApp belum terdaftar'], 404);
        }

        if ($driver['pin'] !== $pin) {
            sendJson(['success' => false, 'message' => 'PIN salah'], 401);
        }

        // Cari order aktif yang sedang dipegang sopir ini
        $orderStmt = $pdo->prepare("
            SELECT o.*, cs.status AS settlement_status, cs.proof_image
            FROM orders o
            LEFT JOIN commission_settlements cs ON o.id = cs.order_id
            WHERE o.driver_id = ? AND o.status NOT IN ('CANCELLED')
            ORDER BY o.id DESC LIMIT 1
        ");
        $orderStmt->execute([$driver['id']]);
        $activeOrder = $orderStmt->fetch();

        // Sensor nomor HP tamu untuk keamanan platform anti-bajak
        if ($activeOrder && !empty($activeOrder['customer_phone'])) {
            $p = $activeOrder['customer_phone'];
            $activeOrder['customer_phone_masked'] = substr($p, 0, 4) . '-****-' . substr($p, -4);
        }

        sendJson([
            'success' => true,
            'message' => 'Login berhasil!',
            'driver' => [
                'id' => $driver['id'],
                'name' => $driver['name'],
                'phone' => $driver['phone'],
                'car_model' => $driver['car_model'],
                'car_plate' => $driver['car_plate'],
                'car_color' => $driver['car_color'],
                'rating' => $driver['rating'],
                'total_trips' => $driver['total_trips']
            ],
            'activeOrder' => $activeOrder ?: null
        ]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 2. UPDATE KOORDINAT GPS SOPIR SECARA BERKALA (DARI HTML5 GEOLOCATION)
if ($action === 'location') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $driverId = intval($input['driver_id'] ?? 0);
    $lat = floatval($input['lat'] ?? 0);
    $lng = floatval($input['lng'] ?? 0);

    if ($driverId <= 0 || empty($lat) || empty($lng)) {
        sendJson(['success' => false, 'message' => 'Parameter tidak valid'], 400);
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE drivers 
            SET current_lat = ?, current_lng = ?, last_location_update = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$lat, $lng, $driverId]);

        sendJson(['success' => true, 'message' => 'Lokasi GPS sopir berhasil diperbarui']);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 3. RIWAYAT PERJALANAN SOPIR
if ($action === 'history') {
    $driverId = intval($_GET['driver_id'] ?? 0);
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, cs.status AS settlement_status, cs.proof_image
            FROM orders o
            LEFT JOIN commission_settlements cs ON o.id = cs.order_id
            WHERE o.driver_id = ?
            ORDER BY o.id DESC LIMIT 20
        ");
        $stmt->execute([$driverId]);
        $history = $stmt->fetchAll();

        sendJson(['success' => true, 'data' => $history]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

sendJson(['success' => false, 'message' => 'Action tidak dikenali'], 404);
