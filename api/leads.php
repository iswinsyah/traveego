<?php
/**
 * API LEADS & CLOSING ENGINE TRAVEEGO (FLAT-FILE JSON STORAGE)
 * Aman, mandiri di Hostinger, tanpa ketergantungan MySQL lokal.
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$dataDir = __DIR__ . '/../data';
$leadsFile = $dataDir . '/leads.json';
$closingsFile = $dataDir . '/closings.json';
$ordersStoreFile = $dataDir . '/orders_store.json';

// Pastikan file penampung data siap
if (!file_exists($leadsFile)) {
    @file_put_contents($leadsFile, json_encode([], JSON_PRETTY_PRINT));
}
if (!file_exists($closingsFile)) {
    @file_put_contents($closingsFile, json_encode([], JSON_PRETTY_PRINT));
}
if (!file_exists($ordersStoreFile)) {
    @file_put_contents($ordersStoreFile, json_encode([], JSON_PRETTY_PRINT));
}

function loadJsonFile($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveJsonFile($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

date_default_timezone_set('Asia/Jakarta');
$now = date('Y-m-d H:i:s');

switch ($action) {
    // 1. REKAM KLIK "PESAN CEPAT" DARI PENGUNJUNG WEB (PUBLIC)
    case 'record_click':
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;

        $car = trim($payload['car'] ?? 'Mobil Pilihan');
        $route = trim($payload['route'] ?? 'Rute Antarkota');
        $source = trim($payload['source'] ?? 'Landing Page');

        $leadId = 'LEAD-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $leads = loadJsonFile($leadsFile);
        $newLead = [
            'id' => $leadId,
            'timestamp' => $now,
            'car' => $car,
            'route' => $route,
            'source' => $source,
            'status' => 'LEAD', // LEAD, NEGOTIATION, CLOSING, LOOSE
            'customer_name' => '',
            'customer_phone' => '',
            'agreed_price' => 0,
            'notes' => '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 150)
        ];

        // Tambah ke paling atas
        array_unshift($leads, $newLead);
        // Batasi maksimal 500 riwayat lead terakhir
        if (count($leads) > 500) {
            $leads = array_slice($leads, 0, 500);
        }
        saveJsonFile($leadsFile, $leads);

        sendJson([
            'success' => true,
            'lead_id' => $leadId,
            'message' => 'Lead berhasil dicatat.'
        ]);
        break;

    // 2. AMBIL DAFTAR LEADS (ADMIN / CREW AI AUTH REQUIRED)
    case 'list':
        if (!isAdminLoggedIn() && !isCrewLoggedIn()) {
            sendJson(['success' => false, 'message' => 'Akses ditolak.'], 401);
        }

        $leads = loadJsonFile($leadsFile);
        $closings = loadJsonFile($closingsFile);

        sendJson([
            'success' => true,
            'total_leads' => count($leads),
            'total_closings' => count($closings),
            'leads' => $leads,
            'closings' => $closings
        ]);
        break;

    // 3. TANDAI STATUS LEAD (NEGOTIATION / LOOSE)
    case 'update_status':
        if (!isAdminLoggedIn() && !isCrewLoggedIn()) {
            sendJson(['success' => false, 'message' => 'Akses ditolak.'], 401);
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;
        $leadId = trim($payload['lead_id'] ?? '');
        $status = strtoupper(trim($payload['status'] ?? 'LEAD'));
        $reason = trim($payload['reason'] ?? '');

        if (!$leadId) {
            sendJson(['success' => false, 'message' => 'ID Lead wajib diisi.'], 400);
        }

        $leads = loadJsonFile($leadsFile);
        $found = false;
        foreach ($leads as &$ld) {
            if ($ld['id'] === $leadId) {
                $ld['status'] = $status;
                $ld['notes'] = $reason ? $reason : $ld['notes'];
                $ld['updated_at'] = $now;
                $found = true;
                break;
            }
        }

        if ($found) {
            saveJsonFile($leadsFile, $leads);
            sendJson(['success' => true, 'message' => "Status lead diubah menjadi $status."]);
        } else {
            sendJson(['success' => false, 'message' => 'Lead tidak ditemukan.'], 404);
        }
        break;

    // 4. HASNA SAVE CLOSING: BUAT NOTIFIKASI REKAP & MAGIC LINK
    case 'save_closing':
        if (!isAdminLoggedIn() && !isCrewLoggedIn()) {
            sendJson(['success' => false, 'message' => 'Akses ditolak.'], 401);
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;

        $custName = trim($payload['customer_name'] ?? '');
        $custPhone = trim($payload['customer_phone'] ?? '');
        $pickupDate = trim($payload['pickup_datetime'] ?? '');
        $pickupAddr = trim($payload['pickup_address'] ?? '');
        $destAddr = trim($payload['destination_address'] ?? '');
        $carType = trim($payload['car_type'] ?? 'Toyota All New Avanza');
        $price = intval($payload['agreed_price'] ?? 0);
        $notes = trim($payload['notes'] ?? 'Tanpa catatan khusus');
        $driverName = trim($payload['driver_name'] ?? 'Pak Budi Santoso (Mitra Terpilih)');
        $carPlate = trim($payload['car_plate'] ?? 'B 1421 KAZ');
        $leadId = trim($payload['lead_id'] ?? '');

        if (!$custName || !$custPhone || !$pickupAddr || !$destAddr) {
            sendJson(['success' => false, 'message' => 'Nama, nomor WhatsApp, titik jemput, dan tujuan wajib diisi.'], 400);
        }

        // Generate Tracking Code / Magic Link
        $trackingCode = 'TRX-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $magicLink = "https://dropiff.id/trip.html?code=" . $trackingCode;
        $formattedPrice = 'Rp ' . number_format($price, 0, ',', '.');

        // Susun Ringkasan Closing Resmi Mbak Hasna
        $summaryText = "📄 *BUKTI KONFIRMASI PEMESANAN TRAVEEGO*\n"
            . "*Kode Booking:* `#" . $trackingCode . "`\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "👤 *Nama Pelanggan:* " . $custName . "\n"
            . "📱 *WhatsApp:* " . $custPhone . "\n"
            . "📅 *Jadwal Jemput:* " . $pickupDate . "\n"
            . "📍 *Titik Jemput:* " . $pickupAddr . "\n"
            . "🏁 *Tujuan:* " . $destAddr . "\n"
            . "🚗 *Armada:* " . $carType . " (All-In Tol & BBM)\n"
            . "💵 *Tarif Disepakati:* " . $formattedPrice . " (0% DP - Bayar ke Sopir)\n"
            . "📝 *Catatan:* " . $notes . "\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "🚘 *Mitra Sopir:* " . $driverName . " (" . $carPlate . ")\n"
            . "📲 *Link Pantau Armada & Chat Sopir:*\n"
            . "👉 " . $magicLink . "\n\n"
            . "*(SOP Traveego: Kami akan mengirimkan reminder posisi armada H-1 malam dan H-2 jam sebelum penjemputan)*";

        $closingData = [
            'tracking_code' => $trackingCode,
            'lead_id' => $leadId,
            'created_at' => $now,
            'customer_name' => $custName,
            'customer_phone' => $custPhone,
            'pickup_datetime' => $pickupDate,
            'pickup_address' => $pickupAddr,
            'destination_address' => $destAddr,
            'car_type' => $carType,
            'agreed_price' => $price,
            'formatted_price' => $formattedPrice,
            'notes' => $notes,
            'driver_name' => $driverName,
            'car_plate' => $carPlate,
            'magic_link' => $magicLink,
            'summary_text' => $summaryText,
            'status' => 'ACCEPTED'
        ];

        // 1. Simpan ke closings.json
        $closings = loadJsonFile($closingsFile);
        array_unshift($closings, $closingData);
        saveJsonFile($closingsFile, $closings);

        // 2. Simpan ke orders_store.json agar trip.html bisa membaca
        $ordersStore = loadJsonFile($ordersStoreFile);
        $ordersStore[$trackingCode] = [
            'tracking_code' => $trackingCode,
            'customer_name' => $custName,
            'customer_phone' => substr($custPhone, 0, 4) . '-****-' . substr($custPhone, -4),
            'pickup_address' => $pickupAddr,
            'destination_address' => $destAddr,
            'pickup_datetime' => $pickupDate,
            'car_type' => $carType,
            'car_model' => $carType,
            'car_plate' => $carPlate,
            'car_color' => 'Hitam / Silver',
            'driver_name' => $driverName,
            'total_fare' => $price,
            'status' => 'ACCEPTED',
            'current_lat' => -6.2088,
            'current_lng' => 106.8456,
            'created_at' => $now
        ];
        saveJsonFile($ordersStoreFile, $ordersStore);

        // 3. Update lead jika ada
        if ($leadId) {
            $leads = loadJsonFile($leadsFile);
            foreach ($leads as &$ld) {
                if ($ld['id'] === $leadId) {
                    $ld['status'] = 'CLOSING';
                    $ld['customer_name'] = $custName;
                    $ld['customer_phone'] = $custPhone;
                    $ld['agreed_price'] = $price;
                    $ld['tracking_code'] = $trackingCode;
                    $ld['updated_at'] = $now;
                    break;
                }
            }
            saveJsonFile($leadsFile, $leads);
        }

        // Juga coba simpan ke MySQL jika koneksi aktif, tapi abaikan jika belum ada MySQL
        try {
            $pdo = getDB();
            if ($pdo) {
                $stmt = $pdo->prepare("
                    INSERT INTO orders (
                        tracking_code, customer_name, customer_phone,
                        pickup_address, destination_address, pickup_datetime,
                        car_type, total_fare, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ACCEPTED')
                ");
                $stmt->execute([
                    $trackingCode, $custName, $custPhone,
                    $pickupAddr, $destAddr, $pickupDate,
                    $carType, $price
                ]);
            }
        } catch (Exception $e) {
            // MySQL dilewati dengan aman, data tetap tersimpan 100% di Flat-File JSON
        }

        sendJson([
            'success' => true,
            'message' => 'Closing berhasil dicatat!',
            'tracking_code' => $trackingCode,
            'magic_link' => $magicLink,
            'summary_text' => $summaryText,
            'order' => $closingData
        ]);
        break;

    default:
        sendJson(['success' => false, 'message' => 'Aksi tidak valid.'], 400);
}
