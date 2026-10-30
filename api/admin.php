<?php
// Deploy v1.2: CS AI WhatsApp Hotline Config
require_once __DIR__ . '/../config.php';
$action = $_GET['action'] ?? $_POST['action'] ?? 'stats';

// 0. AKSI AUTENTIKASI ADMIN
if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        startAdminSession();
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = ADMIN_USERNAME;
        $_SESSION['admin_login_time'] = time();

        sendJson([
            'success' => true,
            'message' => 'Login berhasil! Mengalihkan...',
            'user' => ADMIN_USERNAME
        ]);
    } else {
        sendJson([
            'success' => false,
            'message' => 'Username atau password yang Anda masukkan salah!'
        ], 401);
    }
}

if ($action === 'logout') {
    startAdminSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    sendJson([
        'success' => true,
        'message' => 'Berhasil keluar (logout)'
    ]);
}

if ($action === 'check_auth') {
    sendJson([
        'success' => true,
        'logged_in' => isAdminLoggedIn(),
        'user' => $_SESSION['admin_user'] ?? null
    ]);
}

// PROTEKSI WAJIB LOGIN UNTUK SEMUA AKSI DI BAWAH INI
if ($action !== 'fleet') {
    requireAdminAuth();
}

// 1. STATISTIK OPERASIONAL & DAFTAR TRANSAKSI
if ($action === 'stats') {
    try {
        $pdo = getDB();
        $ordersTotal = $pdo->query("SELECT COUNT(*) AS total FROM orders")->fetch()['total'];
        $auctionCount = $pdo->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'AUCTION'")->fetch()['total'];
        $completedCount = $pdo->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'COMPLETED'")->fetch()['total'];
        $driversCount = $pdo->query("SELECT COUNT(*) AS total FROM drivers")->fetch()['total'];

        $commissions = $pdo->query("
            SELECT SUM(amount) AS total 
            FROM commission_settlements 
            WHERE status = 'VERIFIED_PAID'
        ")->fetch()['total'] ?? 0;

        $latestOrders = $pdo->query("
            SELECT o.*, d.name AS driver_name, cs.status AS settlement_status
            FROM orders o
            LEFT JOIN drivers d ON o.driver_id = d.id
            LEFT JOIN commission_settlements cs ON o.id = cs.order_id
            ORDER BY o.id DESC LIMIT 15
        ")->fetchAll();

        $pendingSettlements = $pdo->query("
            SELECT cs.*, o.tracking_code, o.total_fare, d.name AS driver_name, d.phone AS driver_phone
            FROM commission_settlements cs
            JOIN orders o ON cs.order_id = o.id
            JOIN drivers d ON cs.driver_id = d.id
            WHERE cs.status = 'WAITING_CONFIRMATION'
            ORDER BY cs.created_at DESC
        ")->fetchAll();

        sendJson([
            'success' => true,
            'stats' => [
                'totalOrders' => intval($ordersTotal),
                'activeAuctions' => intval($auctionCount),
                'completedOrders' => intval($completedCount),
                'totalDrivers' => intval($driversCount),
                'totalCommissionPaid' => floatval($commissions)
            ],
            'latestOrders' => $latestOrders,
            'pendingSettlements' => $pendingSettlements,
            'adminBank' => [
                'bank' => ADMIN_BANK_NAME,
                'account' => ADMIN_BANK_ACCOUNT,
                'holder' => ADMIN_BANK_HOLDER
            ]
        ]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 2. VERIFIKASI SETORAN KOMISI 1-KLIK OLEH ADMIN
if ($action === 'verify_settlement') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = intval($input['settlement_id'] ?? 0);
    $status = trim($input['status'] ?? 'VERIFIED_PAID');

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE commission_settlements SET status = ?, verified_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);

        sendJson([
            'success' => true,
            'message' => $status === 'VERIFIED_PAID' ? 'Setoran komisi berhasil diverifikasi LUNAS!' : 'Setoran ditolak.'
        ]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 3. RADAR FLEET SEBARAN ARMADA (UNTUK PETA LEAFLET ADMIN)
if ($action === 'fleet') {
    try {
        $pdo = getDB();
        $stmt = $pdo->query("
            SELECT d.id, d.name, d.phone, d.car_model, d.car_plate, d.car_color, d.rating,
                   d.current_lat, d.current_lng, d.last_location_update, d.is_online,
                   o.tracking_code, o.status AS trip_status
            FROM drivers d
            LEFT JOIN orders o ON d.id = o.driver_id AND o.status IN ('ACCEPTED', 'ON_THE_WAY', 'ARRIVED', 'IN_TRIP')
            WHERE d.is_online = 1
        ");
        $fleet = $stmt->fetchAll();

        sendJson(['success' => true, 'data' => $fleet]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 4. CMS FRONT-END: AMBIL KONTEN SITUS
if ($action === 'get_site_content') {
    $dataFile = __DIR__ . '/../data/site_content.json';
    if (!file_exists($dataFile)) {
        sendJson(['success' => false, 'message' => 'File konfigurasi belum ada'], 404);
    }
    $raw = file_get_contents($dataFile);
    $data = json_decode($raw, true);
    if ($data === null) {
        sendJson(['success' => false, 'message' => 'Format JSON tidak valid'], 500);
    }
    sendJson([
        'success' => true,
        'data' => $data
    ]);
}

// 5. CMS FRONT-END: SIMPAN KONTEN SITUS
if ($action === 'save_site_content') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || empty($input)) {
        sendJson(['success' => false, 'message' => 'Data konten tidak boleh kosong'], 400);
    }

    $dataFile = __DIR__ . '/../data/site_content.json';
    $dataDir = dirname($dataFile);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $jsonString = json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonString === false) {
        sendJson(['success' => false, 'message' => 'Gagal mengonversi data ke format JSON'], 500);
    }

    $saved = file_put_contents($dataFile, $jsonString, LOCK_EX);
    if ($saved === false) {
        sendJson(['success' => false, 'message' => 'Gagal menyimpan ke data/site_content.json. Periksa izin folder.'], 500);
    }

    sendJson([
        'success' => true,
        'message' => '🎉 Pengaturan front-end berhasil disimpan dan dipublikasikan!',
        'saved_at' => date('Y-m-d H:i:s')
    ]);
}

// 5b. GET & SAVE NOMOR WHATSAPP CS AI (HOTLINE) & TOKEN FONNTE
if ($action === 'get_hotline_wa') {
    $dataFile = __DIR__ . '/../data/site_content.json';
    $data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
    $wa = $data['head']['hotline_wa'] ?? ($data['hero']['wa_number'] ?? '6281234567891');
    $aiConfigFile = __DIR__ . '/../data/ai_server_config.json';
    $aiConfig = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];
    $fonnteToken = $aiConfig['fonnte_token'] ?? '';
    sendJson([
        'success' => true,
        'hotline_wa' => $wa,
        'fonnte_token' => $fonnteToken
    ]);
}

if ($action === 'save_hotline_wa') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;
    $wa = preg_replace('/[^0-9]/', '', trim($payload['hotline_wa'] ?? ''));
    if (!$wa) {
        sendJson(['success' => false, 'message' => 'Nomor WhatsApp tidak boleh kosong.'], 400);
    }
    $dataFile = __DIR__ . '/../data/site_content.json';
    $data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
    $data['head'] = $data['head'] ?? [];
    $data['hero'] = $data['hero'] ?? [];
    $data['head']['hotline_wa'] = $wa;
    $data['hero']['wa_number'] = $wa;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // Simpan Fonnte Token jika dikirim
    $aiConfigFile = __DIR__ . '/../data/ai_server_config.json';
    $aiConfig = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];
    if (isset($payload['fonnte_token'])) {
        $aiConfig['fonnte_token'] = trim($payload['fonnte_token']);
        $aiConfig['updated_at'] = date('Y-m-d H:i:s');
        file_put_contents($aiConfigFile, json_encode($aiConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    sendJson([
        'success' => true,
        'message' => "Pengaturan WhatsApp CS AI & Token Fonnte berhasil disimpan!",
        'hotline_wa' => $wa,
        'fonnte_token' => $aiConfig['fonnte_token'] ?? ''
    ]);
}

if ($action === 'test_fonnte') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;
    $token = trim($payload['fonnte_token'] ?? '');
    if (!$token) {
        $aiConfigFile = __DIR__ . '/../data/ai_server_config.json';
        $aiConfig = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];
        $token = trim($aiConfig['fonnte_token'] ?? '');
    }
    if (!$token) {
        sendJson(['success' => false, 'message' => 'Token Fonnte belum diisi.'], 400);
    }

    $url = "https://api.fonnte.com/device";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Authorization: $token"],
        CURLOPT_TIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($res, true);
    if ($httpCode === 200 && ($data['status'] ?? false)) {
        $device = $data['device'] ?? 'WhatsApp Terhubung';
        $devStatus = $data['device_status'] ?? 'connect';
        sendJson([
            'success' => true,
            'message' => "Token Fonnte VALID & TERHUBUNG! Nomor: $device (Status: $devStatus)"
        ]);
    } else {
        $reason = $data['reason'] ?? ($data['message'] ?? "Gagal verifikasi (HTTP $httpCode)");
        sendJson([
            'success' => false,
            'message' => "Uji Fonnte gagal: $reason"
        ], 400);
    }
}

// 5c. TRAINING & PROMPT SYSTEM CS AI (MBAK HASNA)
$hasnaPromptFile = __DIR__ . '/../data/hasna_prompt_training.txt';
$defaultHasnaPrompt = "Kamu adalah Mbak Hasna, Customer Service AI resmi dari Dropoff.id (Layanan Sewa Mobil Privat Drop-Off Antarkota & Antar-Jemput Bandara Sekali Jalan).\n\n"
    . "KARAKTER & PERSONA:\n"
    . "- Ramah, santun, hangat, solutif, persuasif, dan komunikatif khas Customer Service profesional Indonesia.\n"
    . "- Gunakan sapaan hangat seperti \"Halo Kak!\", \"Ada yang bisa Hasna bantu?\", dan gunakan emoji ramah khas WhatsApp 😊.\n"
    . "- Komunikasi ringkas, padat, dan terstruktur (1 sampai 3 paragraf singkat, gunakan poin emoji jika memberikan daftar harga/opsi armada).\n\n"
    . "PRINSIP UTAMA LAYANAN DROPOFF.ID:\n"
    . "1. TARIF ALL-IN BERSIH: Semua harga sudah mencakup Sewa Mobil Privat + Jasa Sopir Berpengalaman + BBM Penuh + Saldo Tol Penuh (Tol MBZ, Tol Cipularang, Tol Bandara Sedyatmo, JORR). Tidak ada pungutan liar apapun.\n"
    . "2. 100% AMAN (0% DP): Bebas uang muka / DP Rp 0. Pembayaran dilakukan 100% setelah tiba dengan selamat di tujuan, baik secara tunai maupun transfer ke sopir.\n"
    . "3. ARMADA PRIVAT (Bukan Travel Gabungan/Umum):\n"
    . "   - Toyota Avanza / Veloz (1-4 penumpang)\n"
    . "   - Toyota Innova Reborn (1-5 penumpang)\n"
    . "   - Toyota HiAce Premio (8-14 penumpang)\n"
    . "   Sopir standby 30 menit sebelum jadwal penjemputan door-to-door.\n\n"
    . "GAYA MENJAWAB & CLOSING PERCAKAPAN:\n"
    . "- Jawab langsung pertanyaan calon tamu mengenai ketersediaan armada, kenyamanan, atau estimasi harga.\n"
    . "- Jelaskan bahwa pemesanan adalah sewa mobil privat (1 mobil khusus untuk rombongan pemesan).\n"
    . "- Di akhir balasan, selalu tanyakan: \"Rencana perjalanannya untuk tanggal dan jam berapa Kak? Serta titik jemput dan tujuannya ke mana agar Hasna siapkan armada terbaiknya 😊\".";

if ($action === 'get_hasna_prompt') {
    $prompt = file_exists($hasnaPromptFile) ? trim(file_get_contents($hasnaPromptFile)) : '';
    if (empty($prompt)) {
        $prompt = $defaultHasnaPrompt;
        @file_put_contents($hasnaPromptFile, $defaultHasnaPrompt);
    }

    $aiConfigFile = __DIR__ . '/../data/ai_server_config.json';
    $aiConfig = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];

    sendJson([
        'success' => true,
        'prompt' => $prompt,
        'default_prompt' => $defaultHasnaPrompt,
        'active_provider' => $aiConfig['active_cloud_provider'] ?? 'gemini',
        'has_gemini' => !empty($aiConfig['gemini_api_key']),
        'has_groq' => !empty($aiConfig['groq_api_key']),
        'updated_at' => file_exists($hasnaPromptFile) ? date('Y-m-d H:i:s', filemtime($hasnaPromptFile)) : date('Y-m-d H:i:s')
    ]);
}

if ($action === 'save_hasna_prompt') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;
    $newPrompt = trim($payload['prompt'] ?? '');
    if (empty($newPrompt)) {
        sendJson(['success' => false, 'message' => 'Prompt training tidak boleh kosong.'], 400);
    }
    $saved = @file_put_contents($hasnaPromptFile, $newPrompt);
    if ($saved === false) {
        sendJson(['success' => false, 'message' => 'Gagal menulis ke file data/hasna_prompt_training.txt. Periksa permission.'], 500);
    }
    sendJson([
        'success' => true,
        'message' => 'Prompt Training Mbak Hasna BERHASIL Disimpan! Otak CS AI langsung menggunakan instruksi ini secara realtime.',
        'prompt' => $newPrompt,
        'char_count' => strlen($newPrompt)
    ]);
}

if ($action === 'reset_hasna_prompt') {
    @file_put_contents($hasnaPromptFile, $defaultHasnaPrompt);
    sendJson([
        'success' => true,
        'message' => 'Prompt Training Mbak Hasna telah direset kembali ke Standar Resmi Dropoff.id.',
        'prompt' => $defaultHasnaPrompt
    ]);
}

if ($action === 'test_hasna_sim') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;
    $userMsg = trim($payload['message'] ?? 'Halo Admin Dropoff.id, sewa Avanza drop-off ke Depok Sukmajaya berapa ya?');
    $customPrompt = trim($payload['prompt'] ?? '');
    if (empty($customPrompt)) {
        $customPrompt = file_exists($hasnaPromptFile) ? trim(file_get_contents($hasnaPromptFile)) : $defaultHasnaPrompt;
    }

    $aiConfigFile = __DIR__ . '/../data/ai_server_config.json';
    $aiConfig = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];
    $geminiKey = trim($aiConfig['gemini_api_key'] ?? '');
    $groqKey = trim($aiConfig['groq_api_key'] ?? '');
    $activeProv = $aiConfig['active_cloud_provider'] ?? 'gemini';

    // Rangkai konteks dengan matriks harga
    $matrixFile = __DIR__ . '/../data/cost_matrix.json';
    $matrix = file_exists($matrixFile) ? json_decode(file_get_contents($matrixFile), true) : [];
    $routes = $matrix['routes'] ?? [];
    $soettaAvanza = $routes['jakarta_bandara_soetta']['pricing']['avanza']['formatted_price'] ?? 'Rp 260.000';
    $barayaAvanza = $routes['jakarta_baraya']['pricing']['avanza']['formatted_price'] ?? 'Rp 500.000';
    $kotaAvanza   = $routes['jabodetabek_dalam_kota']['pricing']['avanza']['formatted_price'] ?? 'Rp 275.000';

    $fullSystem = $customPrompt . "\n\n"
        . "DATA TARIF REAL-TIME ACUAN RESMI:\n"
        . "- Bandara Soetta: Mulai $soettaAvanza (All-In)\n"
        . "- Dalam Kota: Mulai $kotaAvanza (All-In)\n"
        . "- Jakarta ke Bandung: Mulai $barayaAvanza (All-In)";

    $simReply = '';
    $engine = 'Rule Engine';

    $order = ($activeProv === 'groq') ? ['groq', 'gemini'] : ['gemini', 'groq'];
    foreach ($order as $prov) {
        if ($prov === 'gemini' && $geminiKey && !$simReply) {
            $model = $aiConfig['gemini_model'] ?? 'gemini-1.5-flash';
            if ($model === 'gemini-2.5-flash') $model = 'gemini-1.5-flash';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($geminiKey);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'contents' => [['parts' => [['text' => $fullSystem . "\n\nPertanyaan Tamu: " . $userMsg]]]]
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $res = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($http === 200 && $res) {
                $p = json_decode($res, true);
                $simReply = trim($p['candidates'][0]['content']['parts'][0]['text'] ?? '');
                if ($simReply) $engine = "Gemini ($model)";
            }
        } elseif ($prov === 'groq' && $groqKey && !$simReply) {
            $model = $aiConfig['groq_model'] ?? 'llama-3.1-8b-instant';
            if ($model === 'llama-3.3-70b-versatile') $model = 'llama-3.1-8b-instant';
            $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $fullSystem],
                        ['role' => 'user', 'content' => $userMsg]
                    ]
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer $groqKey"],
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $res = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($http === 200 && $res) {
                $p = json_decode($res, true);
                $simReply = trim($p['choices'][0]['message']['content'] ?? '');
                if ($simReply) $engine = "Groq ($model)";
            }
        }
    }

    if (!$simReply) {
        $simReply = "Halo Kak! Salam hangat dari Mbak Hasna (CS AI Dropoff.id) 😊\n\nUntuk drop-off rute tersebut, tarif kami sudah ALL-IN bersih (Mobil Privat + Driver Berpengalaman + BBM + Tol Penuh) dan TANPA DP (bayar di tempat setelah tiba).\n\nRencana untuk penjemputan jam berapa dan tanggal berapa Kak?";
    }

    sendJson([
        'success' => true,
        'reply' => $simReply,
        'engine' => $engine,
        'message_tested' => $userMsg
    ]);
}

// 5d. MANAJEMEN OBJECTION HANDLING & CONTINUOUS LEARNING EVALUATIONS
$objectionsFile = __DIR__ . '/../data/hasna_objections.json';
$evaluationsFile = __DIR__ . '/../data/hasna_evaluations.json';

if ($action === 'get_objections_and_evals') {
    $objections = file_exists($objectionsFile) ? json_decode(file_get_contents($objectionsFile), true) : [];
    $evaluations = file_exists($evaluationsFile) ? json_decode(file_get_contents($evaluationsFile), true) : [];
    
    // Ambil riwayat chat terbaru dari data/leads.json untuk dianalisis/dievaluasi
    $leadsFile = __DIR__ . '/../data/leads.json';
    $leads = file_exists($leadsFile) ? json_decode(file_get_contents($leadsFile), true) : [];
    if (!is_array($leads)) $leads = [];

    sendJson([
        'success' => true,
        'objections' => $objections ?: [],
        'evaluations' => $evaluations ?: [],
        'recent_chats' => array_slice($leads, 0, 10)
    ]);
}

if ($action === 'save_objection') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;
    
    $category = trim($payload['category'] ?? 'Umum');
    $trigger = trim($payload['trigger'] ?? '');
    $strategy = trim($payload['strategy'] ?? '');
    $sample_response = trim($payload['sample_response'] ?? '');

    if (empty($trigger) || empty($sample_response)) {
        sendJson(['success' => false, 'message' => 'Trigger keberatan dan Contoh Jawaban wajib diisi.'], 400);
    }

    $objections = file_exists($objectionsFile) ? json_decode(file_get_contents($objectionsFile), true) : [];
    if (!is_array($objections)) $objections = [];

    $newId = 'KB-' . date('ymd-His');
    $newEntry = [
        'id' => $newId,
        'category' => $category,
        'trigger' => $trigger,
        'strategy' => $strategy,
        'sample_response' => $sample_response,
        'is_active' => true,
        'created_at' => date('Y-m-d H:i:s')
    ];

    array_unshift($objections, $newEntry);
    file_put_contents($objectionsFile, json_encode($objections, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    sendJson([
        'success' => true,
        'message' => 'Taktik Menjawab Keberatan BERHASIL Disimpan ke Memori Mbak Hasna!',
        'entry' => $newEntry
    ]);
}

if ($action === 'delete_objection') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;
    $id = trim($payload['id'] ?? '');

    $objections = file_exists($objectionsFile) ? json_decode(file_get_contents($objectionsFile), true) : [];
    if (is_array($objections)) {
        $filtered = array_filter($objections, function($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        });
        file_put_contents($objectionsFile, json_encode(array_values($filtered), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    sendJson(['success' => true, 'message' => 'Taktik keberatan berhasil dihapus.']);
}

if ($action === 'save_evaluation') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;

    $title = trim($payload['lesson_title'] ?? 'Catatan Evaluasi Baru');
    $instruction = trim($payload['instruction'] ?? '');

    if (empty($instruction)) {
        sendJson(['success' => false, 'message' => 'Pelajaran / instruksi evaluasi tidak boleh kosong.'], 400);
    }

    $evals = file_exists($evaluationsFile) ? json_decode(file_get_contents($evaluationsFile), true) : [];
    if (!is_array($evals)) $evals = [];

    $newId = 'EV-' . date('ymd-His');
    $newEntry = [
        'id' => $newId,
        'lesson_title' => $title,
        'instruction' => $instruction,
        'created_at' => date('Y-m-d H:i:s')
    ];

    array_unshift($evals, $newEntry);
    file_put_contents($evaluationsFile, json_encode($evals, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    sendJson([
        'success' => true,
        'message' => 'Pelajaran baru berhasil diajarkan ke Mbak Hasna! Memori langsung terupdate.',
        'entry' => $newEntry
    ]);
}

if ($action === 'delete_evaluation') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;
    $id = trim($payload['id'] ?? '');

    $evals = file_exists($evaluationsFile) ? json_decode(file_get_contents($evaluationsFile), true) : [];
    if (is_array($evals)) {
        $filtered = array_filter($evals, function($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        });
        file_put_contents($evaluationsFile, json_encode(array_values($filtered), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    sendJson(['success' => true, 'message' => 'Catatan evaluasi berhasil dihapus.']);
}

// 6. SETUP / INISIALISASI DATABASE MYSQL (1-KLIK)
if ($action === 'init_db') {
    try {
        $pdo = getDB();
        $schemaFile = __DIR__ . '/../scripts/schema.sql';
        $seedFile = __DIR__ . '/../scripts/seed.sql';
        if (file_exists($schemaFile)) {
            $pdo->exec(file_get_contents($schemaFile));
        }
        if (file_exists($seedFile)) {
            $pdo->exec(file_get_contents($seedFile));
        }
        sendJson(['success' => true, 'message' => 'Database MySQL berhasil diinisialisasi dan diisi data awal!']);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => 'Gagal inisialisasi database: ' . $e->getMessage()], 500);
    }
}

sendJson(['success' => false, 'message' => 'Action tidak dikenali'], 404);


