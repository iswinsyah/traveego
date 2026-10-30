<?php
require_once __DIR__ . '/../config.php';

$pdo = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. LOGIN MITRA SOPIR & SUPER ADMIN
if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $phone = trim($input['phone'] ?? '');
    $pin = trim($input['pin'] ?? '');

    // Proteksi Keamanan Anti-Brute Force (Maksimal 5x percobaan salah dalam 15 menit per IP)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $throttleFile = __DIR__ . '/../data/driver_login_throttle.json';
    $now = time();
    $throttle = file_exists($throttleFile) ? (json_decode(file_get_contents($throttleFile), true) ?: []) : [];

    if (isset($throttle[$ip])) {
        $ipData = $throttle[$ip];
        if (($ipData['attempts'] ?? 0) >= 5 && ($now - ($ipData['last_attempt'] ?? 0)) < 900) {
            $remainingMin = ceil((900 - ($now - $ipData['last_attempt'])) / 60);
            sendJson([
                'success' => false,
                'message' => "Terlalu banyak percobaan login yang salah. Akses ditangguhkan sementara demi keamanan. Silakan coba lagi dalam {$remainingMin} menit."
            ], 429);
        } elseif (($now - ($ipData['last_attempt'] ?? 0)) >= 900) {
            unset($throttle[$ip]);
        }
    }

    try {
        // A. JALUR SUPER ADMIN (Kredensial winsyah / khilafet@1924)
        if (function_exists('verifySuperAdminDriver') && verifySuperAdminDriver($phone, $pin)) {
            // Reset throttle counter IP
            if (isset($throttle[$ip])) {
                unset($throttle[$ip]);
                @file_put_contents($throttleFile, json_encode($throttle, JSON_PRETTY_PRINT));
            }

            // Pastikan record Super Admin ada di tabel drivers (ID 999)
            try {
                if (isset($pdo) && $pdo) {
                    $stmtCheck = $pdo->prepare("SELECT id FROM drivers WHERE id = 999 OR phone = 'winsyah'");
                    $stmtCheck->execute();
                    $adminDriver = $stmtCheck->fetch();
                    if (!$adminDriver) {
                        $insStmt = $pdo->prepare("
                            INSERT INTO drivers (id, name, phone, pin, city_id, car_model, car_plate, car_color, is_online, is_trusted, rating, total_trips, current_lat, current_lng, last_location_update)
                            VALUES (999, 'Winsyah (Super Admin)', 'winsyah', 'PROTECTED', 1, 'Toyota HiAce Premio Luxury', 'B 1924 WIN', 'Hitam Metalik', 1, 1, 5.0, 999, -6.175392, 106.827153, NOW())
                        ");
                        $insStmt->execute();
                    }
                }
            } catch (Exception $e) {}

            startAdminSession();
            $_SESSION['driver_logged_in'] = true;
            $_SESSION['is_super_admin'] = true;
            $_SESSION['driver_id'] = 999;
            $_SESSION['admin_user'] = 'winsyah';

            // Ambil order aktif jika ada
            $activeOrder = null;
            if (isset($pdo) && $pdo) {
                $orderStmt = $pdo->prepare("
                    SELECT o.*, cs.status AS settlement_status, cs.proof_image
                    FROM orders o
                    LEFT JOIN commission_settlements cs ON o.id = cs.order_id
                    WHERE o.driver_id = 999 AND o.status NOT IN ('CANCELLED')
                    ORDER BY o.id DESC LIMIT 1
                ");
                $orderStmt->execute();
                $activeOrder = $orderStmt->fetch();

                if ($activeOrder && !empty($activeOrder['customer_phone'])) {
                    $p = $activeOrder['customer_phone'];
                    $activeOrder['customer_phone_masked'] = substr($p, 0, 4) . '-****-' . substr($p, -4);
                }
            }

            sendJson([
                'success' => true,
                'message' => 'Selamat datang Super Admin Winsyah! Akses Penuh Sistem Sopir Aktif.',
                'driver' => [
                    'id' => 999,
                    'name' => 'Winsyah (Super Admin)',
                    'phone' => 'winsyah',
                    'car_model' => 'Toyota HiAce Premio Luxury',
                    'car_plate' => 'B 1924 WIN',
                    'car_color' => 'Hitam Metalik',
                    'rating' => 5.0,
                    'total_trips' => 999,
                    'is_super_admin' => true,
                    'role_badge' => 'SUPER ADMIN'
                ],
                'activeOrder' => $activeOrder ?: null
            ]);
        }

        // B. JALUR MITRA SOPIR REGULER (DARI DATABASE)
        $stmt = $pdo->prepare("SELECT * FROM drivers WHERE phone = ?");
        $stmt->execute([$phone]);
        $driver = $stmt->fetch();

        if (!$driver || $driver['pin'] !== $pin) {
            // Catat kegagalan ke throttle
            $throttle[$ip] = [
                'attempts' => ($throttle[$ip]['attempts'] ?? 0) + 1,
                'last_attempt' => $now
            ];
            @file_put_contents($throttleFile, json_encode($throttle, JSON_PRETTY_PRINT));

            sendJson(['success' => false, 'message' => 'Nomor WhatsApp atau PIN salah'], 401);
        }

        // Reset throttle jika berhasil login
        if (isset($throttle[$ip])) {
            unset($throttle[$ip]);
            @file_put_contents($throttleFile, json_encode($throttle, JSON_PRETTY_PRINT));
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
                'total_trips' => $driver['total_trips'],
                'is_super_admin' => false
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


// 4. TOGGLE STATUS ONLINE / OFFLINE SOPIR
if ($action === 'toggle_online') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $driverId = intval($input['driver_id'] ?? 0);
    $isOnline = !empty($input['is_online']) ? 1 : 0;

    if ($driverId <= 0) {
        sendJson(['success' => false, 'message' => 'driver_id wajib ada'], 400);
    }

    try {
        $stmt = $pdo->prepare("UPDATE drivers SET is_online = ? WHERE id = ?");
        $stmt->execute([$isOnline, $driverId]);
        sendJson(['success' => true, 'is_online' => (bool)$isOnline]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

sendJson(['success' => false, 'message' => 'Action tidak dikenali'], 404);
