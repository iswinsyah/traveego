<?php
/**
 * WEBHOOK RESMI WHATSAPP GATEWAY (FONNTE -> CS AI HASNA)
 * Menghubungkan pesan WhatsApp calon penumpang secara langsung ke otak AI Mbak Hasna (Gemini & Groq failover).
 * Otomatis membalas chat WhatsApp pelanggan 24 jam nonstop tanpa campur tangan manusia.
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// 0. VERIFIKASI GET DARI FONNTE & DEBUG MONITORING
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['debug'])) {
        header('Content-Type: text/plain; charset=utf-8');
        $aiConfigFile = __DIR__ . '/../data/ai_server_config.json';
        $cfg = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];
        $t = trim($cfg['fonnte_token'] ?? '');
        $maskedToken = strlen($t) > 6 ? substr($t, 0, 4) . '***' . substr($t, -3) . ' (len ' . strlen($t) . ')' : '(kosong)';
        $geminiSet = !empty($cfg['gemini_api_key']) ? 'YES' : 'NO';
        $groqSet = !empty($cfg['groq_api_key']) ? 'YES' : 'NO';

        echo "=== STATUS LIVE KONFIGURASI DROPOFF.ID WA ===\n";
        echo "Fonnte Token : $maskedToken\n";
        echo "Gemini Key   : $geminiSet (Model: " . ($cfg['gemini_model'] ?? 'gemini-3.8-flash') . ")\n";
        echo "Groq Key     : $groqSet (Model: " . ($cfg['groq_model'] ?? 'qwen/qwen3.8-27b') . ")\n";
        echo "Active Prov  : " . ($cfg['active_cloud_provider'] ?? 'gemini') . "\n\n";

        // 1. Cek Status Device Fonnte Langsung
        if ($t) {
            $ch = curl_init('https://api.fonnte.com/device');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ["Authorization: $t"],
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $fonnteDevRes = curl_exec($ch);
            curl_close($ch);
            echo "=== CEK STATUS DEVICE FONNTE ===\n$fonnteDevRes\n\n";
        }

        // 2. Tes Panggilan Singkat ke Gemini
        if (!empty($cfg['gemini_api_key'])) {
            $gKey = $cfg['gemini_api_key'];
            $gModel = !empty($cfg['gemini_model']) && !in_array($cfg['gemini_model'], ['gemini-2.5-flash', 'gemini-1.5-flash']) ? $cfg['gemini_model'] : 'gemini-3.8-flash';
            $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$gModel}:generateContent?key=" . urlencode($gKey));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['contents' => [['parts' => [['text' => 'Halo Hasna']]]]]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $gRes = curl_exec($ch);
            $gCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            echo "=== TES LIVE GEMINI ($gModel) ===\nHTTP Code: $gCode\nRes: " . substr($gRes, 0, 300) . "\n\n";
        }

        // 3. Tes Panggilan Singkat ke Groq
        if (!empty($cfg['groq_api_key'])) {
            $qKey = $cfg['groq_api_key'];
            $qModel = !empty($cfg['groq_model']) && !in_array($cfg['groq_model'], ['llama-3.1-8b-instant', 'llama-3.3-70b-versatile']) ? $cfg['groq_model'] : 'qwen/qwen3.8-27b';
            $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $qModel,
                    'messages' => [['role' => 'user', 'content' => 'Halo Hasna']],
                    'max_tokens' => 50
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer $qKey"],
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $qRes = curl_exec($ch);
            $qCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            echo "=== TES LIVE GROQ ($qModel) ===\nHTTP Code: $qCode\nRes: " . substr($qRes, 0, 300) . "\n\n";
        }

        echo "=== RECENT WA INCOMING & OUTGOING LOGS ===\n";
        $logFile = __DIR__ . '/../data/wa_debug.log';
        echo file_exists($logFile) ? file_get_contents($logFile) : "(Belum ada request dari Fonnte)\n";
        exit;
    }
    sendJson(['status' => true, 'message' => 'Dropoff.id WhatsApp Webhook CS AI Hasna siap menerima pesan!']);
}

// 0.1 HANDLER INJEKSI KONFIGURASI AMAN (RUNTIME SETUP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['setup']) && $_GET['setup'] === 'hasna2026') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
    if (!empty($data['fonnte_token']) || !empty($data['gemini_api_key'])) {
        $aiConfigFile = __DIR__ . '/../data/ai_server_config.json';
        $cfg = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];
        if (!is_array($cfg)) $cfg = [];
        if (!empty($data['fonnte_token'])) $cfg['fonnte_token'] = trim($data['fonnte_token']);
        if (!empty($data['gemini_api_key'])) $cfg['gemini_api_key'] = trim($data['gemini_api_key']);
        if (!empty($data['gemini_model'])) $cfg['gemini_model'] = trim($data['gemini_model']);
        if (!empty($data['groq_api_key'])) $cfg['groq_api_key'] = trim($data['groq_api_key']);
        if (!empty($data['groq_model'])) $cfg['groq_model'] = trim($data['groq_model']);
        if (!empty($data['active_cloud_provider'])) $cfg['active_cloud_provider'] = trim($data['active_cloud_provider']);
        $cfg['updated_at'] = date('Y-m-d H:i:s');
        @file_put_contents($aiConfigFile, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        sendJson(['status' => true, 'message' => 'Konfigurasi Hasna AI berhasil disimpan ke server live!', 'saved' => $cfg]);
    }
}

// 1. TERIMA PAYLOAD DARI FONNTE (JSON / POST FORM)
$rawInput = file_get_contents('php://input');
@file_put_contents(__DIR__ . '/../data/wa_debug.log', date('Y-m-d H:i:s') . " | Raw: " . ($rawInput ?: json_encode($_POST)) . PHP_EOL, FILE_APPEND);

$payload = json_decode($rawInput, true);
if (!is_array($payload) || empty($payload)) {
    $payload = $_POST;
}

$sender = preg_replace('/[^0-9]/', '', $payload['sender'] ?? ($payload['from'] ?? ''));
$device = preg_replace('/[^0-9]/', '', $payload['device'] ?? '');
$incomingMsg = trim($payload['message'] ?? ($payload['text'] ?? ''));
$senderName = trim($payload['name'] ?? 'Tamu');

// Abaikan jika tidak ada nomor pengirim atau pesan kosong
if (empty($sender) || empty($incomingMsg)) {
    sendJson(['status' => false, 'message' => 'No sender or message provided']);
}

// Abaikan pesan grup WhatsApp agar tidak membalas obrolan grup atau menghabiskan kuota
$isGroup = !empty($payload['isgroup']) || strpos($sender, '-') !== false || (!empty($payload['senderlid']) && strpos($payload['senderlid'], 'g.us') !== false);
if ($isGroup) {
    sendJson(['status' => false, 'message' => 'Group message ignored']);
}

// Abaikan laporan absensi otomatis atau bot broadcast
if (preg_match('/(LAPORAN ABSENSI|Absen (Masuk|Pulang)|Villa Quran)/i', $incomingMsg)) {
    sendJson(['status' => false, 'message' => 'Automated report broadcast ignored']);
}

// Untuk tes: jika chat dikirim dari nomor bot itu sendiri, tetap izinkan jika mengandung kata pesan / sewa / hasna
$isTestSelf = (!empty($device) && $sender === $device);
if ($isTestSelf && !preg_match('/(hasna|pesan|sewa|drop|tarif|halo|mobil|avanza|innova|hiace|tes|test)/i', $incomingMsg)) {
    sendJson(['status' => false, 'message' => 'Ignored background self message']);
}

// 2. LOAD KONFIGURASI AI SERVER & TOKEN FONNTE
$aiConfigFile = __DIR__ . '/../data/ai_server_config.json';
$aiConfig = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];

$fonnteToken = trim($aiConfig['fonnte_token'] ?? '');
$geminiKey = trim($aiConfig['gemini_api_key'] ?? '');
$groqKey = trim($aiConfig['groq_api_key'] ?? '');
$activeProvider = $aiConfig['active_cloud_provider'] ?? 'gemini';

// 3. LOAD DATA MATRIKS HARGA RESMI PAK HENDRA
$matrixFile = __DIR__ . '/../data/cost_matrix.json';
$matrix = file_exists($matrixFile) ? json_decode(file_get_contents($matrixFile), true) : [];
$routes = $matrix['routes'] ?? [];

$soettaPrices = $routes['jakarta_bandara_soetta']['pricing'] ?? [];
$halimPrices  = $routes['jakarta_bandara_halim']['pricing'] ?? [];
$barayaPrices = $routes['jakarta_baraya']['pricing'] ?? [];
$kotaPrices   = $routes['jabodetabek_dalam_kota']['pricing'] ?? [];

$soettaAvanza = $soettaPrices['avanza']['formatted_price'] ?? 'Rp 250.000';
$soettaInnova = $soettaPrices['innova']['formatted_price'] ?? 'Rp 325.000';
$soettaHiace  = $soettaPrices['hiace']['formatted_price'] ?? 'Rp 400.000';

$barayaAvanza = $barayaPrices['avanza']['formatted_price'] ?? 'Rp 500.000';
$barayaInnova = $barayaPrices['innova']['formatted_price'] ?? 'Rp 700.000';
$barayaHiace  = $barayaPrices['hiace']['formatted_price'] ?? 'Rp 1.400.000';

$kotaAvanza = $kotaPrices['avanza']['formatted_price'] ?? 'Rp 275.000';
$kotaInnova = $kotaPrices['innova']['formatted_price'] ?? 'Rp 350.000';
$kotaHiace  = $kotaPrices['hiace']['formatted_price'] ?? 'Rp 550.000';

// 4. GENERATE BALASAN CS AI HASNA (CLOUD LLM GEMINI & GROQ FAILOVER)
$aiReply = null;
$sourceEngine = 'Rule Engine';

if (!empty($geminiKey) || !empty($groqKey)) {
    $promptFile = __DIR__ . '/../data/hasna_prompt_training.txt';
    $customPrompt = file_exists($promptFile) ? trim(file_get_contents($promptFile)) : '';

    // Ambil Memori Penanganan Keberatan (Objection Handling) & Evaluasi Pembelajaran Harian
    $objectionsFile = __DIR__ . '/../data/hasna_objections.json';
    $objectionData = file_exists($objectionsFile) ? json_decode(file_get_contents($objectionsFile), true) : [];
    $objectionContext = "";
    if (is_array($objectionData) && !empty($objectionData)) {
        $objectionContext .= "\n\nPANDUAN RESMI MENGATASI KEBERATAN & NEGOSIASI HARGA (OBJECTION HANDLING):";
        foreach ($objectionData as $ob) {
            if ($ob['is_active'] ?? true) {
                $objectionContext .= "\n- JIKA TAMU: \"" . $ob['trigger'] . "\"";
                $objectionContext .= "\n  STRATEGI & CONTOH JAWABAN: " . ($ob['strategy'] ?? '') . " -> \"" . ($ob['sample_response'] ?? '') . "\"";
            }
        }
    }

    $evalsFile = __DIR__ . '/../data/hasna_evaluations.json';
    $evalData = file_exists($evalsFile) ? json_decode(file_get_contents($evalsFile), true) : [];
    $evalContext = "";
    if (is_array($evalData) && !empty($evalData)) {
        $evalContext .= "\n\nCATATAN EVALUASI & INSTRUKSI PEMBELAJARAN DARI BOS:";
        foreach ($evalData as $ev) {
            $evalContext .= "\n- " . ($ev['lesson_title'] ?? '') . ": " . ($ev['instruction'] ?? '');
        }
    }

    if (!empty($customPrompt)) {
        $systemContext = $customPrompt . "\n\n"
            . $objectionContext . $evalContext . "\n\nDATA MATRIKS TARIF RESMI PAK HENDRA (ACUAN REAL-TIME):\n"
            . "- Antar Jemput Bandara Soetta/Halim: Avanza $soettaAvanza, Innova $soettaInnova, HiAce $soettaHiace\n"
            . "- Dalam Kota Jabodetabek: Avanza $kotaAvanza, Innova $kotaInnova, HiAce $kotaHiace\n"
            . "- Jakarta / Jabodetabek ke Bandung (Baraya): Avanza $barayaAvanza, Innova $barayaInnova, HiAce $barayaHiace\n"
            . "INSTRUKSI GAYA BICARA WHATSAPP (HEMAT TOKEN & ALAMI SEPERTI MANUSIA): Jawab langsung to-the-point dalam 1-3 kalimat pendek. Gunakan bahasa percakapan santai, wajar, dan sopan seperti admin WhatsApp manusia biasa. DILARANG bertele-tele, DILARANG membuat paragraf panjang, dan JANGAN mengulang-ulang promosi/fasilitas kecuali jika tamu bertanya.";
    } else {
        $systemContext = "Kamu adalah Mbak Hasna, Customer Service AI resmi dari Dropoff.id (layanan sewa mobil privat drop-off sekali jalan antarkota).\n"
            . "Karakter: Ramah, santun, hangat, solutif, persuasif, memakai sapaan khas ('Halo Kak!', 'Ada yang bisa Hasna bantu?').\n"
            . "Prinsip Utama Layanan Dropoff.id:\n"
            . "1. TARIF ALL-IN BERSIH: Sudah mencakup Sewa Mobil Privat (bukan travel umum/gabungan) + Jasa Sopir Berpengalaman + BBM + Saldo Tol Penuh (Tol MBZ, Cipularang, Sedyatmo Bandara, JORR). Tidak ada pungutan liar apapun.\n"
            . "2. PEMBAYARAN AMAN (0% DP): Tidak ada uang muka. Pembayaran 100% dibayarkan via cash atau transfer langsung ke sopir setelah tiba dengan selamat di tujuan.\n"
            . "3. ARMADA & KOORDINASI (BANG JAKA): Pilihan armada: Avanza/Veloz (1-4 penumpang), Innova Reborn (1-5 penumpang), Toyota HiAce Premio (8-14 penumpang). Sopir standby 30 menit sebelum jadwal penjemputan door-to-door.\n"
            . "4. TARIF ACUAN RESMI:\n"
            . "   - Antar Jemput Bandara Soetta/Halim: Avanza $soettaAvanza, Innova $soettaInnova, HiAce $soettaHiace\n"
            . "   - Dalam Kota Jabodetabek: Avanza $kotaAvanza, Innova $kotaInnova, HiAce $kotaHiace\n"
            . "   - Jakarta / Jabodetabek ke Bandung (Baraya): Avanza $barayaAvanza, Innova $barayaInnova, HiAce $barayaHiace\n"
            . "INSTRUKSI GAYA BICARA WHATSAPP (HEMAT TOKEN & ALAMI SEPERTI MANUSIA): Jawab langsung to-the-point dalam 1-3 kalimat pendek. Gunakan bahasa percakapan santai, wajar, dan sopan seperti admin WhatsApp manusia biasa. DILARANG bertele-tele, DILARANG membuat paragraf panjang, dan JANGAN mengulang-ulang promosi/fasilitas kecuali jika tamu bertanya.";
    }

    $order = ($activeProvider === 'groq') ? ['groq', 'gemini'] : ['gemini', 'groq'];
    $configUpdated = false;

    foreach ($order as $prov) {
        if ($prov === 'gemini' && $geminiKey && !$aiReply) {
            $configuredModel = $aiConfig['gemini_model'] ?? 'gemini-3.8-flash';
            if (in_array($configuredModel, ['gemini-2.5-flash', 'gemini-1.5-flash']) || empty($configuredModel)) {
                $configuredModel = 'gemini-3.8-flash';
            }
            $geminiCandidates = array_unique([$configuredModel, 'gemini-3.8-flash', 'gemini-2.5-flash', 'gemini-1.5-flash']);
            $fullPrompt = $systemContext . "\n\nPertanyaan Calon Tamu WhatsApp (" . ($senderName ?: 'Tamu') . "): " . $incomingMsg;
            $payloadGemini = json_encode([
                'contents' => [
                    ['parts' => [['text' => $fullPrompt]]]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 250
                ]
            ]);

            foreach ($geminiCandidates as $model) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($geminiKey);
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payloadGemini,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_TIMEOUT => 7,
                    CURLOPT_SSL_VERIFYPEER => false
                ]);
                $res = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);
                @file_put_contents(__DIR__ . '/../data/wa_debug.log', date('Y-m-d H:i:s') . " | Gemini ($model) HTTP: $httpCode | Res: " . substr($res ?: $curlErr, 0, 120) . "\n", FILE_APPEND);

                if ($httpCode === 200 && $res) {
                    $parsed = json_decode($res, true);
                    $text = $parsed['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if ($text) {
                        $aiReply = trim($text);
                        $sourceEngine = "Gemini ($model)";
                        if (($aiConfig['gemini_model'] ?? '') !== $model) {
                            $aiConfig['gemini_model'] = $model;
                            $configUpdated = true;
                        }
                        break;
                    }
                }
            }
        } elseif ($prov === 'groq' && $groqKey && !$aiReply) {
            $configuredModel = $aiConfig['groq_model'] ?? 'qwen/qwen3.8-27b';
            if (in_array($configuredModel, ['llama-3.1-8b-instant', 'llama-3.3-70b-versatile']) || empty($configuredModel)) {
                $configuredModel = 'qwen/qwen3.8-27b';
            }
            $groqCandidates = array_unique([$configuredModel, 'qwen/qwen3.8-27b', 'openai/gpt-oss-120b', 'openai/gpt-oss-20b', 'allam-2-7b']);

            foreach ($groqCandidates as $model) {
                $url = "https://api.groq.com/openai/v1/chat/completions";
                $payloadGroq = json_encode([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemContext],
                        ['role' => 'user', 'content' => $incomingMsg]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 250
                ]);

                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payloadGroq,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $groqKey
                    ],
                    CURLOPT_TIMEOUT => 7,
                    CURLOPT_SSL_VERIFYPEER => false
                ]);
                $res = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);
                @file_put_contents(__DIR__ . '/../data/wa_debug.log', date('Y-m-d H:i:s') . " | Groq ($model) HTTP: $httpCode | Res: " . substr($res ?: $curlErr, 0, 120) . "\n", FILE_APPEND);

                if ($httpCode === 200 && $res) {
                    $parsed = json_decode($res, true);
                    $text = $parsed['choices'][0]['message']['content'] ?? '';
                    if ($text) {
                        $aiReply = trim($text);
                        $sourceEngine = "Groq ($model)";
                        if (($aiConfig['groq_model'] ?? '') !== $model) {
                            $aiConfig['groq_model'] = $model;
                            $configUpdated = true;
                        }
                        break;
                    }
                }
            }
        }
    }

    if ($configUpdated) {
        @file_put_contents($aiConfigFile, json_encode($aiConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

// 5. FALLBACK CERDAS JIKA API CLOUD BELUM DIISI ATAU ERROR
if (!$aiReply) {
    $msgLower = strtolower($incomingMsg);
    if (preg_match('/(harga|tarif|biaya|berapa|ongkos)/', $msgLower)) {
        if (preg_match('/(bandara|soetta|halim|cgk)/', $msgLower)) {
            $aiReply = "Halo Kak! Salam hangat dari Mbak Hasna (CS Dropoff.id) 😊\n\nUntuk antar-jemput Bandara (Soetta & Halim):\n🚗 Avanza/Veloz: $soettaAvanza (All-In)\n✨ Innova Reborn: $soettaInnova (All-In)\n🚐 HiAce Premio: $soettaHiace (All-In)\n\nSemua tarif sudah ALL-IN (Mobil privat + Sopir + Tol Bandara + BBM). 0% DP bayar di tujuan!\n\nRencana untuk penerbangan jam berapa dan tanggal berapa Kak?";
        } elseif (preg_match('/(baraya|bandung|cimahi)/', $msgLower)) {
            $aiReply = "Halo Kak! Hasna siap bantu drop-off rute favorit Jakarta &ndash; Bandung (Baraya) PP:\n🚗 Avanza: $barayaAvanza (All-In)\n✨ Innova Reborn: $barayaInnova (All-In)\n🚐 HiAce Premio: $barayaHiace (All-In)\n\nSudah termasuk Tol MBZ, Tol Cipularang, BBM, dan Sopir. Tanpa DP sepeserpun!\n\nUntuk penjemputan tanggal berapa dan titik jemputnya di mana Kak?";
        } else {
            $aiReply = "Halo Kak! Hasna siap bantu layanan sewa mobil privat drop-off sekali jalan:\n✈️ Bandara Soetta: Mulai $soettaAvanza\n🏙️ Dalam Kota: Mulai $kotaAvanza\n🛣️ Jakarta &ndash; Bandung: Mulai $barayaAvanza\n\nTarif sudah ALL-IN Bersih (Mobil + BBM + Tol + Sopir), bayar saat tiba di tujuan. Mau rute yang mana Kak?";
        }
    } else {
        $aiReply = "Halo Kak! Terima kasih sudah menghubungi Dropoff.id. Saya Mbak Hasna, CS AI resmi Dropoff.id 😊\n\nKami melayani sewa mobil privat drop-off antarkota (Bandara Soetta/Halim, Jabodetabek, dan Bandung Raya) dengan sistem ALL-IN (sudah termasuk Mobil + Sopir + BBM + Tol Penuh) dan TANPA DP (bayar tunai/transfer saat tiba di tujuan).\n\nAda rute perjalanan yang bisa Hasna siapkan armadanya untuk Kakak?";
    }
}

// 6. KIRIM BALASAN KE WHATSAPP PENGIRIM VIA API FONNTE
$sendResult = null;
if (!empty($fonnteToken)) {
    $postData = [
        'target' => $sender,
        'message' => $aiReply
    ];
    if (!empty($payload['inboxid'])) {
        $postData['inboxid'] = $payload['inboxid'];
    }

    $ch = curl_init('https://api.fonnte.com/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => ["Authorization: $fonnteToken"],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $sendResult = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);
    @file_put_contents(__DIR__ . '/../data/wa_debug.log', date('Y-m-d H:i:s') . " | Outgoing to $sender | Fonnte Res: " . ($sendResult ?: "Err: $curlErr") . " | Engine: $sourceEngine\n", FILE_APPEND);
}

// 7. ARSIPKAN PERCAKAPAN KE LEADS / CRM
$leadsFile = __DIR__ . '/../data/leads.json';
$leads = file_exists($leadsFile) ? json_decode(file_get_contents($leadsFile), true) : [];
if (!is_array($leads)) $leads = [];

$leadEntry = [
    'id' => 'WA-' . date('ymd-His') . '-' . substr($sender, -4),
    'name' => $senderName ?: 'Tamu WA',
    'phone' => $sender,
    'incoming_message' => $incomingMsg,
    'hasna_reply' => $aiReply,
    'source' => 'WhatsApp Gateway (Fonnte)',
    'engine' => $sourceEngine,
    'created_at' => date('Y-m-d H:i:s')
];
array_unshift($leads, $leadEntry);
if (count($leads) > 100) $leads = array_slice($leads, 0, 100);
@file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// 8. RESPON KE FONNTE (Mendukung auto-reply instan via response webhook Fonnte)
sendJson([
    'status' => true,
    'reply' => $aiReply,
    'message' => 'Balasan berhasil diproses dan dikirim ke pengirim via WhatsApp',
    'sender' => $sender,
    'engine' => $sourceEngine,
    'fonnte_response' => json_decode($sendResult, true) ?? $sendResult
]);
