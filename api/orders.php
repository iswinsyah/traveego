<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/ledger_helper.php';

$pdo = getDB(false);
if ($pdo) {
    initLedgerTables($pdo);
}
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

// Flat-file Store Helpers (Tahan banting jika MySQL belum terkoneksi di hosting)
$ordersStoreFile = __DIR__ . '/../data/orders_store.json';

function getFlatStore() {
    global $ordersStoreFile;
    if (file_exists($ordersStoreFile)) {
        return json_decode(file_get_contents($ordersStoreFile), true) ?: [];
    }
    return [];
}

function saveFlatStore($store) {
    global $ordersStoreFile;
    $dir = dirname($ordersStoreFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return file_put_contents($ordersStoreFile, json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// HELPER NOTIFIKASI WHATSAPP OTOMATIS (FONNTE / WABLAS / FALLBACK LOG)
function sendWhatsAppNotification($phone, $message) {
    if (empty($phone) || empty($message)) return false;

    // Bersihkan nomor telepon (08xxx -> 628xxx)
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($cleanPhone, '0')) {
        $cleanPhone = '62' . substr($cleanPhone, 1);
    }

    $fonnteToken = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : (getenv('FONNTE_TOKEN') ?: '');
    if (!empty($fonnteToken)) {
        try {
            $ch = curl_init('https://api.fonnte.com/send');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'target' => $cleanPhone,
                'message' => $message,
                'countryCode' => '62'
            ]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: $fonnteToken"
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_exec($ch);
            curl_close($ch);
        } catch (Throwable $e) {}
    }

    // Logging lokal untuk audit admin
    $logDir = __DIR__ . '/../data/';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . 'wa_notifications.log';
    $logEntry = date('Y-m-d H:i:s') . " | TO: {$cleanPhone} | MSG: " . str_replace(["\r", "\n"], ' ', $message) . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    return true;
}

// 1. DAFTAR ORDER LELANG TERBUKA (UNTUK RADAR PWA SOPIR)
if ($action === 'auction' || ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($action))) {
    $reqDriverId = intval($_GET['driver_id'] ?? 0);
    if ($reqDriverId > 0 && $reqDriverId != 999) {
        $driverBalance = 0;
        $isSuper = false;
        if ($pdo) {
            try {
                $stD = $pdo->prepare("SELECT balance, is_super_admin FROM drivers WHERE id = ?");
                $stD->execute([$reqDriverId]);
                $rowD = $stD->fetch();
                if ($rowD) {
                    $driverBalance = floatval($rowD['balance'] ?? 0);
                    $isSuper = !empty($rowD['is_super_admin']);
                }
            } catch (Throwable $e) {}
        }
        if (!$isSuper && $driverBalance <= 0) {
            sendJson([
                'success' => true,
                'data' => [],
                'balance_blocked' => true,
                'current_balance' => $driverBalance,
                'message' => 'Saldo deposit Anda kosong (Rp 0). Anda tidak dapat melihat dan menerima orderan lelang sebelum melakukan Top Up saldo deposit minimal Rp 50.000.'
            ]);
        }
    }

    try {
        $orders = [];
        if ($pdo) {
            $stmt = $pdo->query("
                SELECT o.*, c1.name AS origin_city, c2.name AS destination_city
                FROM orders o
                LEFT JOIN routes r ON o.route_id = r.id
                LEFT JOIN cities c1 ON r.origin_city_id = c1.id
                LEFT JOIN cities c2 ON r.destination_city_id = c2.id
                WHERE o.status = 'AUCTION'
                ORDER BY o.is_emergency DESC, o.created_at DESC
            ");
            $orders = $stmt->fetchAll();
        } else {
            // Fallback flat-file orders_store.json
            $store = getFlatStore();
            foreach ($store as $k => $ord) {
                if (($ord['status'] ?? 'AUCTION') === 'AUCTION') {
                    $orders[] = [
                        'id' => $ord['id'] ?? $k,
                        'tracking_code' => $ord['tracking_code'] ?? $k,
                        'customer_name' => $ord['customer_name'] ?? 'Tamu Dropoff.id',
                        'customer_phone' => $ord['customer_phone'] ?? '0812-****-7890',
                        'customer_phone_masked' => $ord['customer_phone_masked'] ?? '0812-****-7890',
                        'pickup_address' => $ord['pickup_address'] ?? 'Bandara Soetta T3',
                        'destination_address' => $ord['destination_address'] ?? 'Bandung Kota',
                        'pickup_datetime' => $ord['pickup_datetime'] ?? date('Y-m-d H:i:s', strtotime('+2 hours')),
                        'passenger_count' => intval($ord['passenger_count'] ?? 2),
                        'car_type' => $ord['car_type'] ?? 'Toyota Innova Reborn',
                        'total_fare' => floatval($ord['total_fare'] ?? 1050000),
                        'admin_commission' => floatval($ord['admin_commission'] ?? 105000),
                        'pickup_lat' => isset($ord['pickup_lat']) ? floatval($ord['pickup_lat']) : null,
                        'pickup_lng' => isset($ord['pickup_lng']) ? floatval($ord['pickup_lng']) : null,
                        'dest_lat' => isset($ord['dest_lat']) ? floatval($ord['dest_lat']) : null,
                        'dest_lng' => isset($ord['dest_lng']) ? floatval($ord['dest_lng']) : null,
                        'is_emergency' => intval($ord['is_emergency'] ?? 0),
                        'emergency_reason' => $ord['emergency_reason'] ?? '',
                        'status' => 'AUCTION',
                        'created_at' => $ord['created_at'] ?? date('Y-m-d H:i:s')
                    ];
                }
            }
        }
        sendJson(['success' => true, 'data' => $orders]);
    } catch (Throwable $e) {
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
        if ($pdo) {
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
        } else {
            // Flat-file fallback
            $store = getFlatStore();
            $maskedPhone = (strlen($custPhone) >= 8) ? substr($custPhone, 0, 4) . '-****-' . substr($custPhone, -4) : $custPhone;
            $newOrder = [
                'id' => time(),
                'tracking_code' => $trackingCode,
                'customer_name' => $custName,
                'customer_phone' => $custPhone,
                'customer_phone_masked' => $maskedPhone,
                'pickup_address' => $pickup,
                'destination_address' => $dest,
                'pickup_datetime' => $pickupTime,
                'car_type' => $carType,
                'total_fare' => $totalFare,
                'admin_commission' => $commission,
                'status' => 'AUCTION',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $store[$trackingCode] = $newOrder;
            saveFlatStore($store);

            sendJson([
                'success' => true,
                'message' => 'Pesanan berhasil diterbitkan ke lelang sopir!',
                'order' => $newOrder
            ]);
        }
    } catch (Throwable $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 3. AMBIL ORDER (ATOMIC LOCK: SIAPA CEPAT DIA DAPAT)
if ($action === 'accept') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $orderId = trim($input['order_id'] ?? '');
    $driverId = intval($input['driver_id'] ?? 0);

    if (empty($orderId) || $driverId <= 0) {
        sendJson(['success' => false, 'message' => 'Parameter order_id dan driver_id wajib ada'], 400);
    }

    // ATURAN SALDO MINIMUM KOMISI: Saldo driver harus mencukupi untuk komisi lelang ini
    if ($driverId != 999) {
        $driverBalance = 0;
        $isSuper = false;
        $requiredCommission = 0;
        if ($pdo) {
            try {
                $stD = $pdo->prepare("SELECT balance, is_super_admin FROM drivers WHERE id = ?");
                $stD->execute([$driverId]);
                $rowD = $stD->fetch();
                if ($rowD) {
                    $driverBalance = floatval($rowD['balance'] ?? 0);
                    $isSuper = !empty($rowD['is_super_admin']);
                }

                $stO = $pdo->prepare("SELECT total_fare, admin_commission, commission_amount FROM orders WHERE (id = ? OR tracking_code = ?)");
                $stO->execute([$orderId, $orderId]);
                $ordPreview = $stO->fetch();
                if ($ordPreview) {
                    $requiredCommission = floatval($ordPreview['commission_amount'] ?? 0);
                    if ($requiredCommission <= 0) {
                        $requiredCommission = floatval($ordPreview['admin_commission'] ?? 0);
                    }
                    if ($requiredCommission <= 0) {
                        $requiredCommission = floatval($ordPreview['total_fare'] ?? 0) * 0.10;
                    }
                }
            } catch (Throwable $e) {}
        }

        if (!$isSuper) {
            if ($driverBalance <= 0) {
                sendJson([
                    'success' => false,
                    'code' => 'INSUFFICIENT_BALANCE',
                    'current_balance' => $driverBalance,
                    'required_commission' => $requiredCommission,
                    'message' => 'Gagal mengambil order! Saldo deposit Anda kosong (Rp ' . number_format($driverBalance, 0, ',', '.') . '). Silakan Top Up saldo deposit minimal Rp 50.000 terlebih dahulu.'
                ], 403);
            } elseif ($requiredCommission > 0 && $driverBalance < $requiredCommission) {
                $shortage = $requiredCommission - $driverBalance;
                sendJson([
                    'success' => false,
                    'code' => 'INSUFFICIENT_BALANCE',
                    'current_balance' => $driverBalance,
                    'required_commission' => $requiredCommission,
                    'shortage' => $shortage,
                    'message' => 'Gagal mengambil order! Saldo deposit Anda (Rp ' . number_format($driverBalance, 0, ',', '.') . ') tidak mencukupi untuk potongan komisi lelang ini (Rp ' . number_format($requiredCommission, 0, ',', '.') . '). Silakan Top Up minimal Rp ' . number_format($shortage, 0, ',', '.') . ' terlebih dahulu.'
                ], 403);
            }
        }
    }

    try {
        if ($pdo) {
            // Query Atomic Lock di MySQL
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET driver_id = ?, status = 'ACCEPTED', driver_assigned_at = NOW() 
                WHERE (id = ? OR tracking_code = ?) AND status = 'AUCTION' AND driver_id IS NULL
            ");
            $stmt->execute([$driverId, $orderId, $orderId]);

            if ($stmt->rowCount() === 1) {
                $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? OR tracking_code = ?");
                $orderStmt->execute([$orderId, $orderId]);
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
        } else {
            // Atomic Lock di Flat-File JSON store
            $store = getFlatStore();
            $targetKey = null;

            foreach ($store as $k => $ord) {
                if ((string)$k === (string)$orderId || 
                    (string)($ord['id'] ?? '') === (string)$orderId || 
                    (string)($ord['tracking_code'] ?? '') === (string)$orderId) {
                    $targetKey = $k;
                    break;
                }
            }

            if ($targetKey === null) {
                sendJson(['success' => false, 'message' => 'Order tidak ditemukan'], 404);
            }

            if (($store[$targetKey]['status'] ?? '') !== 'AUCTION') {
                sendJson([
                    'success' => false,
                    'message' => 'Maaf, order ini baru saja diambil oleh rekan sopir lain!'
                ], 409);
            }

            // Kunci order untuk sopir pemenang
            $store[$targetKey]['status'] = 'ACCEPTED';
            $store[$targetKey]['driver_id'] = $driverId;
            $store[$targetKey]['driver_assigned_at'] = date('Y-m-d H:i:s');

            if ($driverId == 999) {
                $store[$targetKey]['driver_name'] = 'Winsyah (Super Admin)';
                $store[$targetKey]['driver_phone'] = '081234567891';
                $store[$targetKey]['car_model'] = 'Toyota HiAce Premio Luxury';
                $store[$targetKey]['car_plate'] = 'B 1924 WIN';
                $store[$targetKey]['car_color'] = 'Hitam Metalik';
                $store[$targetKey]['driver_rating'] = 5.0;
            } else {
                $store[$targetKey]['driver_name'] = 'Mitra Dropoff.id';
                $store[$targetKey]['driver_phone'] = '081234567891';
                $store[$targetKey]['car_model'] = $store[$targetKey]['car_type'] ?? 'Toyota Avanza';
                $store[$targetKey]['car_plate'] = 'B 1234 ABC';
                $store[$targetKey]['car_color'] = 'Silver';
                $store[$targetKey]['driver_rating'] = 4.9;
            }

            saveFlatStore($store);

            sendJson([
                'success' => true,
                'message' => 'Selamat! Anda memenangkan lelang order ini.',
                'data' => $store[$targetKey]
            ]);
        }
    } catch (Throwable $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 4. UPDATE STATUS PERJALANAN (MENUJU JEMPUT, TIBA, JALAN, SELESAI)
if ($action === 'status') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $orderId = trim($input['order_id'] ?? '');
    $driverId = intval($input['driver_id'] ?? 0);
    $newStatus = trim($input['status'] ?? '');

    $valid = ['ON_THE_WAY', 'ARRIVED', 'IN_TRIP', 'COMPLETED', 'CANCELLED'];
    if (!in_array($newStatus, $valid)) {
        sendJson(['success' => false, 'message' => 'Status tidak valid'], 400);
    }

    try {
        if ($pdo) {
            $sql = "UPDATE orders SET status = ? WHERE (id = ? OR tracking_code = ?) AND driver_id = ?";
            if ($newStatus === 'COMPLETED') {
                $sql = "UPDATE orders SET status = ?, completed_at = NOW() WHERE (id = ? OR tracking_code = ?) AND driver_id = ?";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newStatus, $orderId, $orderId, $driverId]);

            // PEMBUKUAN OTOMATIS SAAT PELAYANAN KE PELANGGAN DITUNAIKAN (COMPLETED)
            if ($newStatus === 'COMPLETED') {
                try {
                    $ordStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? OR tracking_code = ?");
                    $ordStmt->execute([$orderId, $orderId]);
                    $ordData = $ordStmt->fetch();

                    if ($ordData) {
                        $commAmount = floatval($ordData['commission_amount'] ?? 0);
                        if ($commAmount <= 0) {
                            $commAmount = floatval($ordData['admin_commission'] ?? 0);
                        }
                        if ($commAmount <= 0) {
                            $commAmount = floatval($ordData['total_fare'] ?? 0) * 0.10; // Default 10%
                        }

                        if ($commAmount > 0 && $driverId > 0 && $driverId != 999) {
                            // 1. Potong saldo deposit mitra sopir
                            $pdo->prepare("UPDATE drivers SET balance = GREATEST(0, balance - ?) WHERE id = ?")->execute([$commAmount, $driverId]);

                            // 2. Masukkan ke Buku Kas Platform (Pemasukan Kas Komisi)
                            recordLedgerEntry(
                                'IN',
                                'KOMISI_ORDER',
                                $commAmount,
                                "Pemasukan Komisi Platform Order {$ordData['tracking_code']} (Pelayanan tamu {$ordData['customer_name']} selesai). Saldo dipotong otomatis dari deposit driver.",
                                $driverId,
                                $ordData['id'],
                                $ordData['tracking_code'],
                                'SYSTEM_AUTO'
                            );

                            // 3. Update atau Insert status setoran menjadi LUNAS
                            $pdo->prepare("
                                INSERT INTO commission_settlements (order_id, driver_id, amount, bank_sender, sender_name, status, verified_at, created_at)
                                VALUES (?, ?, ?, 'SALDO_DEPOSIT', 'Potong Saldo Otomatis', 'VERIFIED_PAID', NOW(), NOW())
                                ON DUPLICATE KEY UPDATE status = 'VERIFIED_PAID', verified_at = NOW()
                            ")->execute([$ordData['id'], $driverId, $commAmount]);
                        }
                    }
                } catch (Throwable $deductErr) {
                    error_log("Gagal potong saldo completed: " . $deductErr->getMessage());
                }
            }

            sendJson(['success' => true, 'message' => "Status diperbarui menjadi {$newStatus}"]);
        } else {
            // Flat file update
            $store = getFlatStore();
            $targetKey = null;

            foreach ($store as $k => $ord) {
                if ((string)$k === (string)$orderId || 
                    (string)($ord['id'] ?? '') === (string)$orderId || 
                    (string)($ord['tracking_code'] ?? '') === (string)$orderId) {
                    $targetKey = $k;
                    break;
                }
            }

            if ($targetKey !== null) {
                $store[$targetKey]['status'] = $newStatus;
                if ($newStatus === 'COMPLETED') {
                    $store[$targetKey]['completed_at'] = date('Y-m-d H:i:s');
                }
                saveFlatStore($store);
                sendJson(['success' => true, 'message' => "Status diperbarui menjadi {$newStatus}"]);
            } else {
                sendJson(['success' => false, 'message' => 'Order tidak ditemukan'], 404);
            }
        }
    } catch (Throwable $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 5. KONFIRMASI SETOR KOMISI PASCA-BAYAR (DENGAN UPLOAD STRUK)
if ($action === 'settle') {
    $orderId = trim($_POST['order_id'] ?? '');
    $driverId = intval($_POST['driver_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $bankSender = trim($_POST['bank_sender'] ?? 'BCA');
    $senderName = trim($_POST['sender_name'] ?? 'Mitra Sopir');

    $proofPath = null;
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $ext = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
        $filename = 'struk-' . time() . '-' . rand(100, 999) . '.' . $ext;
        if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $uploadDir . $filename)) {
            $proofPath = '/uploads/' . $filename;
        }
    }

    try {
        if ($pdo) {
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
        } else {
            // Flat store update
            $store = getFlatStore();
            $targetKey = null;

            foreach ($store as $k => $ord) {
                if ((string)$k === (string)$orderId || 
                    (string)($ord['id'] ?? '') === (string)$orderId || 
                    (string)($ord['tracking_code'] ?? '') === (string)$orderId) {
                    $targetKey = $k;
                    break;
                }
            }

            if ($targetKey !== null) {
                $store[$targetKey]['settlement_status'] = 'WAITING_CONFIRMATION';
                $store[$targetKey]['settlement_amount'] = $amount;
                $store[$targetKey]['settlement_bank'] = $bankSender;
                $store[$targetKey]['settlement_sender'] = $senderName;
                $store[$targetKey]['settlement_proof'] = $proofPath;
                $store[$targetKey]['settlement_time'] = date('Y-m-d H:i:s');
                saveFlatStore($store);
            }
        }

        sendJson(['success' => true, 'message' => 'Konfirmasi transfer komisi berhasil dikirim ke Admin!']);
    } catch (Throwable $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 6. TRACKING INFO PELANGGAN + LIVE GPS SOPIR (UNTUK LEAFLET MAP)
if ($action === 'track') {
    $code = trim($_GET['code'] ?? '');
    if (empty($code)) {
        sendJson(['success' => false, 'message' => 'Kode tracking wajib ada'], 400);
    }

    // 1. Cek Flat-File JSON store
    $ordersStoreFile = __DIR__ . '/../data/orders_store.json';
    if (file_exists($ordersStoreFile)) {
        $ordersStore = json_decode(file_get_contents($ordersStoreFile), true);
        if (is_array($ordersStore) && isset($ordersStore[$code])) {
            $order = $ordersStore[$code];
            if (!empty($order['customer_phone'])) {
                $phone = $order['customer_phone'];
                $order['customer_phone_masked'] = substr($phone, 0, 4) . '-****-' . substr($phone, -4);
            }
            sendJson(['success' => true, 'data' => $order]);
        }
    }

    // 2. Cek MySQL jika database sudah terhubung
    try {
        if (isset($pdo) && $pdo) {
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

            if ($order) {
                if (!empty($order['customer_phone'])) {
                    $phone = $order['customer_phone'];
                    $order['customer_phone_masked'] = substr($phone, 0, 4) . '-****-' . substr($phone, -4);
                }
                sendJson(['success' => true, 'data' => $order]);
            }
        }
        sendJson(['success' => false, 'message' => 'Pesanan tidak ditemukan'], 404);
    } catch (Throwable $e) {
        sendJson(['success' => false, 'message' => 'Pesanan tidak ditemukan'], 404);
    }
}

// 7. IN-APP CHAT ANTARA SOPIR DAN TAMU (BERBASIS TRACKING CODE)
if ($action === 'order_chat') {
    $chatDir = __DIR__ . '/../data/order_chats/';
    if (!is_dir($chatDir)) {
        @mkdir($chatDir, 0755, true);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $code = trim($_GET['code'] ?? '');
        if (empty($code)) {
            sendJson(['success' => false, 'message' => 'Kode tracking wajib diisi'], 400);
        }
        $safeCode = preg_replace('/[^A-Za-z0-9\-]/', '', $code);
        $chatFile = $chatDir . $safeCode . '.json';
        $messages = file_exists($chatFile) ? (json_decode(file_get_contents($chatFile), true) ?: []) : [];
        sendJson(['success' => true, 'data' => $messages]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $code = trim($input['code'] ?? '');
        $sender = trim($input['sender'] ?? 'driver'); // 'driver' atau 'customer'
        $text = trim($input['text'] ?? '');
        $name = trim($input['sender_name'] ?? ($sender === 'driver' ? 'Sopir' : 'Penumpang'));

        if (empty($code) || empty($text)) {
            sendJson(['success' => false, 'message' => 'Kode tracking dan pesan wajib diisi'], 400);
        }

        $safeCode = preg_replace('/[^A-Za-z0-9\-]/', '', $code);
        $chatFile = $chatDir . $safeCode . '.json';
        $messages = file_exists($chatFile) ? (json_decode(file_get_contents($chatFile), true) ?: []) : [];

        $newMsg = [
            'id' => uniqid('msg_'),
            'sender' => $sender,
            'name' => htmlspecialchars($name),
            'text' => htmlspecialchars($text),
            'time' => date('H:i')
        ];
        $messages[] = $newMsg;

        if (count($messages) > 100) {
            $messages = array_slice($messages, -100);
        }
        file_put_contents($chatFile, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        sendJson(['success' => true, 'message' => 'Pesan terkirim', 'data' => $newMsg]);
    }
}

// 8. SOPIR KENDALA DARURAT (OPER ORDERAN / RELIST KE LELANG RADAR)
if ($action === 'release_order' || $action === 'emergency_relist') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $orderId = trim($input['order_id'] ?? '');
    $driverId = intval($input['driver_id'] ?? 0);
    $reason = trim($input['reason'] ?? 'Kendala darurat kendaraan / fisik');

    if (empty($orderId) || $driverId <= 0) {
        sendJson(['success' => false, 'message' => 'Parameter order_id dan driver_id wajib diisi'], 400);
    }

    try {
        if ($pdo) {
            // Attempt to update with emergency details
            try {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET status = 'AUCTION', 
                        driver_id = NULL, 
                        is_emergency = 1, 
                        emergency_reason = ?, 
                        driver_assigned_at = NULL 
                    WHERE (id = ? OR tracking_code = ?) 
                      AND (driver_id = ? OR driver_id IS NULL)
                      AND status IN ('ACCEPTED', 'ON_THE_WAY', 'ARRIVED')
                ");
                $stmt->execute([$reason, $orderId, $orderId, $driverId]);
            } catch (Throwable $alterE) {
                // If column doesn't exist yet, fallback to status & driver_id reset
                $stmtFallback = $pdo->prepare("
                    UPDATE orders 
                    SET status = 'AUCTION', driver_id = NULL, driver_assigned_at = NULL 
                    WHERE (id = ? OR tracking_code = ?) 
                      AND (driver_id = ? OR driver_id IS NULL)
                      AND status IN ('ACCEPTED', 'ON_THE_WAY', 'ARRIVED')
                ");
                $stmtFallback->execute([$orderId, $orderId, $driverId]);
            }

            sendJson(['success' => true, 'message' => 'Order berhasil dioper dan kembali ke radar lelang untuk rekan sopir lain.']);
        } else {
            // Flat store update
            $store = getFlatStore();
            $targetKey = null;
            foreach ($store as $k => $ord) {
                if ((string)$k === (string)$orderId || 
                    (string)($ord['id'] ?? '') === (string)$orderId || 
                    (string)($ord['tracking_code'] ?? '') === (string)$orderId) {
                    $targetKey = $k;
                    break;
                }
            }

            if ($targetKey !== null) {
                $store[$targetKey]['status'] = 'AUCTION';
                $store[$targetKey]['driver_id'] = null;
                $store[$targetKey]['driver_name'] = null;
                $store[$targetKey]['driver_phone'] = null;
                $store[$targetKey]['is_emergency'] = 1;
                $store[$targetKey]['emergency_reason'] = $reason;
                $store[$targetKey]['emergency_at'] = date('Y-m-d H:i:s');
                saveFlatStore($store);
                sendJson(['success' => true, 'message' => 'Order berhasil dioper dan kembali ke radar lelang untuk rekan sopir lain.']);
            } else {
                sendJson(['success' => false, 'message' => 'Order tidak ditemukan'], 404);
            }
        }
    } catch (Throwable $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 9. PEMBATALAN OLEH PENUMPANG (CUSTOMER CANCEL)
if ($action === 'cancel_by_customer') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $orderId = trim($input['order_id'] ?? $input['tracking_code'] ?? '');
    $reason = trim($input['reason'] ?? 'Permintaan dibatalkan oleh tamu');

    if (empty($orderId)) {
        sendJson(['success' => false, 'message' => 'Parameter order_id wajib diisi'], 400);
    }

    try {
        if ($pdo) {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'CANCELLED', 
                    notes = CONCAT(COALESCE(notes,''), ' [Batal Penumpang: ', ?) 
                WHERE (id = ? OR tracking_code = ?) AND status != 'COMPLETED'
            ");
            $stmt->execute([$reason, $orderId, $orderId]);
            sendJson(['success' => true, 'message' => 'Pesanan berhasil dibatalkan oleh tamu']);
        } else {
            $store = getFlatStore();
            $targetKey = null;
            foreach ($store as $k => $ord) {
                if ((string)$k === (string)$orderId || 
                    (string)($ord['id'] ?? '') === (string)$orderId || 
                    (string)($ord['tracking_code'] ?? '') === (string)$orderId) {
                    $targetKey = $k;
                    break;
                }
            }
            if ($targetKey !== null) {
                $store[$targetKey]['status'] = 'CANCELLED';
                $store[$targetKey]['cancelled_by'] = 'customer';
                $store[$targetKey]['cancellation_reason'] = $reason;
                saveFlatStore($store);
                sendJson(['success' => true, 'message' => 'Pesanan berhasil dibatalkan oleh tamu']);
            } else {
                sendJson(['success' => false, 'message' => 'Order tidak ditemukan'], 404);
            }
        }
    } catch (Throwable $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 10. PENILAIAN & ULASAN TAMU (RATING & REVIEW)
if ($action === 'rate_trip') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $orderCode = trim($input['code'] ?? $input['order_id'] ?? '');
    $rating = intval($input['rating'] ?? 5);
    $review = trim($input['review'] ?? '');
    $tags = trim($input['tags'] ?? '');

    if ($rating < 1 || $rating > 5 || empty($orderCode)) {
        sendJson(['success' => false, 'message' => 'Rating harus bernilai 1 - 5 bintang dan kode pesanan wajib ada'], 400);
    }

    try {
        if ($pdo) {
            try {
                $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_rating INT DEFAULT NULL");
                $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_review TEXT NULL");
                $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS review_tags VARCHAR(255) NULL");
            } catch (Throwable $e) {}

            $stmt = $pdo->prepare("UPDATE orders SET customer_rating = ?, customer_review = ?, review_tags = ? WHERE tracking_code = ? OR id = ?");
            $stmt->execute([$rating, $review, $tags, $orderCode, $orderCode]);

            sendJson(['success' => true, 'message' => 'Terima kasih atas ulasan dan penilaian bintang Anda!']);
        } else {
            $store = getFlatStore();
            $targetKey = null;
            foreach ($store as $k => $ord) {
                if ((string)$k === (string)$orderCode || 
                    (string)($ord['id'] ?? '') === (string)$orderCode || 
                    (string)($ord['tracking_code'] ?? '') === (string)$orderCode) {
                    $targetKey = $k;
                    break;
                }
            }
            if ($targetKey !== null) {
                $store[$targetKey]['customer_rating'] = $rating;
                $store[$targetKey]['customer_review'] = $review;
                $store[$targetKey]['review_tags'] = $tags;
                $store[$targetKey]['rated_at'] = date('Y-m-d H:i:s');
                saveFlatStore($store);
                sendJson(['success' => true, 'message' => 'Terima kasih atas ulasan dan penilaian bintang Anda!']);
            } else {
                sendJson(['success' => false, 'message' => 'Pesanan tidak ditemukan'], 404);
            }
        }
    } catch (Throwable $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// Default fallback
sendJson(['success' => false, 'message' => 'Action tidak dikenali'], 404);
