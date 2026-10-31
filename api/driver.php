<?php
require_once __DIR__ . '/../config.php';

// Gunakan getDB(false) agar tidak melempar 500 jika MySQL di Hostinger belum terkonfigurasi
$pdo = getDB(false);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper load & save driver flat-file store (Driver yang sudah aktif)
function getDriversStore() {
    $f = __DIR__ . '/../data/drivers_store.json';
    return file_exists($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
}

function saveDriversStore($data) {
    $f = __DIR__ . '/../data/drivers_store.json';
    $dir = dirname($f);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($f, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Helper load & save driver registrations flat-file store (Pendaftaran calon sopir)
function getDriverRegistrations() {
    $f = __DIR__ . '/../data/driver_registrations.json';
    return file_exists($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
}

function saveDriverRegistrations($data) {
    $f = __DIR__ . '/../data/driver_registrations.json';
    $dir = dirname($f);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($f, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Helper upload dokumen pendaftaran sopir (KTP, SIM, Mobil)
function uploadDriverDocument($fileKey, $prefix = 'doc') {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        $ext = 'jpg';
    }
    $uploadDir = __DIR__ . '/../uploads/drivers/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    $filename = $prefix . '-' . time() . '-' . rand(1000, 9999) . '.' . $ext;
    $targetPath = $uploadDir . $filename;
    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
        return '/uploads/drivers/' . $filename;
    }
    return null;
}

// 0. SIGNUP / PENDAFTARAN MITRA SOPIR BARU (FORM 10 FIELD)
if ($action === 'register') {
    $name = trim($_POST['name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $nikSim = trim($_POST['nik_sim'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $carModel = trim($_POST['car_model'] ?? '');
    $carPlate = strtoupper(trim($_POST['car_plate'] ?? ''));

    // Validasi 7 Field Teks
    if (empty($name) || empty($username) || empty($password) || empty($nikSim) || empty($phone) || empty($carModel) || empty($carPlate)) {
        sendJson(['success' => false, 'message' => 'Mohon lengkapi seluruh kolom isian formulir pendaftaran mitra sopir!'], 400);
    }

    // Validasi format username (hanya huruf, angka, underscore)
    if (!preg_match('/^[a-z0-9_]{3,25}$/', $username)) {
        sendJson(['success' => false, 'message' => 'Username hanya boleh terdiri dari huruf kecil, angka, dan garis bawah (_) tanpa spasi (3-25 karakter).'], 400);
    }

    // Normalisasi nomor telepon
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strpos($cleanPhone, '62') === 0) {
        $cleanPhone = '0' . substr($cleanPhone, 2);
    }
    if (strlen($cleanPhone) < 9 || strlen($cleanPhone) > 15) {
        sendJson(['success' => false, 'message' => 'Nomor WhatsApp tidak valid. Masukkan nomor WhatsApp aktif (contoh: 081234567890).'], 400);
    }

    // Cek apakah username / telepon sudah aktif di pool sopir
    $driversStore = getDriversStore();
    foreach ($driversStore as $d) {
        if (($d['username'] ?? '') === $username) {
            sendJson(['success' => false, 'message' => "Username '{$username}' sudah terdaftar. Silakan pilih username lain."], 409);
        }
        if (($d['phone'] ?? '') === $cleanPhone) {
            sendJson(['success' => false, 'message' => "Nomor WhatsApp {$cleanPhone} sudah terdaftar sebagai mitra aktif."], 409);
        }
    }

    // Cek di antrean pendaftaran pending
    $registrations = getDriverRegistrations();
    foreach ($registrations as $r) {
        if (($r['username'] ?? '') === $username && ($r['status'] ?? '') === 'PENDING_ACTIVATION') {
            sendJson(['success' => false, 'message' => "Username '{$username}' sudah diajukan sebelumnya dan sedang menunggu aktivasi Admin."], 409);
        }
        if (($r['phone'] ?? '') === $cleanPhone && ($r['status'] ?? '') === 'PENDING_ACTIVATION') {
            sendJson(['success' => false, 'message' => "Nomor WhatsApp {$cleanPhone} sudah diajukan sebelumnya dan sedang dalam proses verifikasi & aktivasi Admin."], 409);
        }
    }

    // Validasi & Upload 3 Foto Wajib (KTP, SIM, Mobil)
    $photoKtp = uploadDriverDocument('photo_ktp', 'ktp');
    $photoSim = uploadDriverDocument('photo_sim', 'sim');
    $photoCar = uploadDriverDocument('photo_car', 'car');

    if (!$photoKtp) {
        sendJson(['success' => false, 'message' => 'Wajib mengunggah Foto KTP yang jelas dan terbaca!'], 400);
    }
    if (!$photoSim) {
        sendJson(['success' => false, 'message' => 'Wajib mengunggah Foto SIM yang masih berlaku!'], 400);
    }
    if (!$photoCar) {
        sendJson(['success' => false, 'message' => 'Wajib mengunggah Foto Mobil Anda (tampak depan/serong beserta plat nomor)!'], 400);
    }

    $regId = 'DRV-REG-' . strtoupper(substr(uniqid(), -6));
    $newReg = [
        'reg_id' => $regId,
        'name' => htmlspecialchars($name),
        'username' => htmlspecialchars($username),
        'password' => $password,
        'nik_sim' => htmlspecialchars($nikSim),
        'phone' => $cleanPhone,
        'car_model' => htmlspecialchars($carModel),
        'car_plate' => htmlspecialchars($carPlate),
        'photo_ktp' => $photoKtp,
        'photo_sim' => $photoSim,
        'photo_car' => $photoCar,
        'status' => 'PENDING_ACTIVATION',
        'created_at' => date('Y-m-d H:i:s'),
        'activated_at' => null
    ];

    $registrations[$regId] = $newReg;
    saveDriverRegistrations($registrations);

    sendJson([
        'success' => true,
        'message' => 'Pendaftaran mitra sopir berhasil dikirim! Akun Anda sedang menunggu verifikasi dan aktivasi dari Admin Dropoff.id.',
        'data' => [
            'reg_id' => $regId,
            'name' => $newReg['name'],
            'username' => $newReg['username'],
            'phone' => $newReg['phone'],
            'car_model' => $newReg['car_model'],
            'car_plate' => $newReg['car_plate'],
            'status' => 'PENDING_ACTIVATION'
        ]
    ]);
}

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

            // Pastikan record Super Admin ada di tabel MySQL jika aktif
            if ($pdo) {
                try {
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
                } catch (Throwable $e) {}
            }

            startAdminSession();
            $_SESSION['driver_logged_in'] = true;
            $_SESSION['is_super_admin'] = true;
            $_SESSION['driver_id'] = 999;
            $_SESSION['admin_user'] = 'winsyah';

            $activeOrder = null;
            if ($pdo) {
                try {
                    $orderStmt = $pdo->prepare("
                        SELECT o.*, cs.status AS settlement_status, cs.proof_image
                        FROM orders o
                        LEFT JOIN commission_settlements cs ON o.id = cs.order_id
                        WHERE o.driver_id = 999 AND o.status NOT IN ('CANCELLED')
                        ORDER BY o.id DESC LIMIT 1
                    ");
                    $orderStmt->execute();
                    $activeOrder = $orderStmt->fetch();
                } catch (Throwable $e) {}
            }

            if ($activeOrder && !empty($activeOrder['customer_phone'])) {
                $p = $activeOrder['customer_phone'];
                $activeOrder['customer_phone_masked'] = substr($p, 0, 4) . '-****-' . substr($p, -4);
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

        // B. CEK APAKAH AKUN MASIH MENUNGGU AKTIVASI ADMIN (PENDING_ACTIVATION)
        $registrations = getDriverRegistrations();
        $isPending = false;
        foreach ($registrations as $r) {
            $matchIdentity = ($r['phone'] === $phone || ($r['username'] ?? '') === strtolower($phone));
            if ($matchIdentity && ($r['status'] ?? '') === 'PENDING_ACTIVATION') {
                if ($r['password'] === $pin) {
                    $isPending = true;
                    break;
                }
            }
        }

        if ($isPending) {
            sendJson([
                'success' => false,
                'status_code' => 'PENDING_ACTIVATION',
                'message' => 'Akun pendaftaran Anda sedang dalam proses verifikasi & aktivasi oleh Admin Dropoff.id. Nomor WhatsApp Anda akan segera dihubungi dan diaktifkan oleh Admin.'
            ], 403);
        }

        // C. JALUR MITRA SOPIR REGULER (DARI DATABASE ATAU FLAT-FILE FALLBACK)
        $driver = null;
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM drivers WHERE phone = ? OR username = ?");
            $stmt->execute([$phone, $phone]);
            $driver = $stmt->fetch();
        } else {
            $store = getDriversStore();
            foreach ($store as $d) {
                if ($d['phone'] === $phone || ($d['username'] ?? '') === strtolower($phone)) {
                    $driver = $d;
                    break;
                }
            }
        }

        if (!$driver || ($driver['pin'] !== $pin && ($driver['password'] ?? '') !== $pin)) {
            // Catat kegagalan ke throttle
            $throttle[$ip] = [
                'attempts' => ($throttle[$ip]['attempts'] ?? 0) + 1,
                'last_attempt' => $now
            ];
            @file_put_contents($throttleFile, json_encode($throttle, JSON_PRETTY_PRINT));

            sendJson(['success' => false, 'message' => 'Nomor WhatsApp / Username atau PIN/Password salah'], 401);
        }

        // Reset throttle jika berhasil login
        if (isset($throttle[$ip])) {
            unset($throttle[$ip]);
            @file_put_contents($throttleFile, json_encode($throttle, JSON_PRETTY_PRINT));
        }

        $activeOrder = null;
        if ($pdo) {
            try {
                $orderStmt = $pdo->prepare("
                    SELECT o.*, cs.status AS settlement_status, cs.proof_image
                    FROM orders o
                    LEFT JOIN commission_settlements cs ON o.id = cs.order_id
                    WHERE o.driver_id = ? AND o.status NOT IN ('CANCELLED')
                    ORDER BY o.id DESC LIMIT 1
                ");
                $orderStmt->execute([$driver['id']]);
                $activeOrder = $orderStmt->fetch();
            } catch (Throwable $e) {}
        }

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
                'car_color' => $driver['car_color'] ?? 'Hitam',
                'rating' => $driver['rating'] ?? 5.0,
                'total_trips' => $driver['total_trips'] ?? 0,
                'is_super_admin' => false
            ],
            'activeOrder' => $activeOrder ?: null
        ]);
    } catch (Throwable $e) {
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
        if ($pdo) {
            $stmt = $pdo->prepare("
                UPDATE drivers 
                SET current_lat = ?, current_lng = ?, last_location_update = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$lat, $lng, $driverId]);
        } else {
            $store = getDriversStore();
            if (isset($store[(string)$driverId])) {
                $store[(string)$driverId]['current_lat'] = $lat;
                $store[(string)$driverId]['current_lng'] = $lng;
                $store[(string)$driverId]['last_location_update'] = date('Y-m-d H:i:s');
                saveDriversStore($store);
            }
        }

        sendJson(['success' => true, 'message' => 'Lokasi GPS sopir berhasil diperbarui']);
    } catch (Throwable $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 3. RIWAYAT PERJALANAN SOPIR
if ($action === 'history') {
    $driverId = intval($_GET['driver_id'] ?? 0);
    try {
        $history = [];
        if ($pdo) {
            $stmt = $pdo->prepare("
                SELECT o.*, cs.status AS settlement_status, cs.proof_image
                FROM orders o
                LEFT JOIN commission_settlements cs ON o.id = cs.order_id
                WHERE o.driver_id = ?
                ORDER BY o.id DESC LIMIT 20
            ");
            $stmt->execute([$driverId]);
            $history = $stmt->fetchAll();
        } else {
            // Contoh riwayat simulasi untuk flat-file
            $history = [
                [
                    'id' => 101,
                    'tracking_code' => 'TRX-9982',
                    'customer_name' => 'Bpk. Ridwan',
                    'pickup_address' => 'Bandara Soetta T3',
                    'destination_address' => 'Jl. Dago, Bandung',
                    'car_type' => 'Toyota HiAce Premio',
                    'total_fare' => 1750000,
                    'admin_commission' => 175000,
                    'status' => 'COMPLETED',
                    'settlement_status' => 'APPROVED',
                    'completed_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
                ]
            ];
        }

        sendJson(['success' => true, 'data' => $history]);
    } catch (Throwable $e) {
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
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE drivers SET is_online = ? WHERE id = ?");
            $stmt->execute([$isOnline, $driverId]);
        }
        sendJson(['success' => true, 'is_online' => (bool)$isOnline]);
    } catch (Throwable $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

sendJson(['success' => false, 'message' => 'Action tidak dikenali'], 404);
