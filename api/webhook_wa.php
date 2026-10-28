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
        $logFile = __DIR__ . '/../data/wa_debug.log';
        echo file_exists($logFile) ? file_get_contents($logFile) : "=== WA DEBUG LOG KOSONG (Belum ada request dari Fonnte) ===\n";
        exit;
    }
    sendJson(['status' => true, 'message' => 'Traveego WhatsApp Webhook CS AI Hasna siap menerima pesan!']);
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
    $systemContext = "Kamu adalah Mbak Hasna, Customer Service AI resmi dari Traveego (layanan sewa mobil privat drop-off sekali jalan antarkota).\n"
        . "Karakter: Ramah, santun, hangat, solutif, persuasif, memakai sapaan khas ('Halo Kak!', 'Ada yang bisa Hasna bantu?').\n"
        . "Prinsip Utama Layanan Traveego:\n"
        . "1. TARIF ALL-IN BERSIH: Sudah mencakup Sewa Mobil Privat (bukan travel umum/gabungan) + Jasa Sopir Berpengalaman + BBM + Saldo Tol Penuh (Tol MBZ, Cipularang, Sedyatmo Bandara, JORR). Tidak ada pungutan liar apapun.\n"
        . "2. PEMBAYARAN AMAN (0% DP): Tidak ada uang muka. Pembayaran 100% dibayarkan via cash atau transfer langsung ke sopir setelah tiba dengan selamat di tujuan.\n"
        . "3. ARMADA & KOORDINASI (BANG JAKA): Pilihan armada: Avanza/Veloz (1-4 penumpang), Innova Reborn (1-5 penumpang), Toyota HiAce Premio (8-14 penumpang). Sopir standby 30 menit sebelum jadwal penjemputan door-to-door.\n"
        . "4. TARIF ACUAN RESMI:\n"
        . "   - Antar Jemput Bandara Soetta/Halim: Avanza $soettaAvanza, Innova $soettaInnova, HiAce $soettaHiace\n"
        . "   - Dalam Kota Jabodetabek: Avanza $kotaAvanza, Innova $kotaInnova, HiAce $kotaHiace\n"
        . "   - Jakarta / Jabodetabek ke Bandung (Baraya): Avanza $barayaAvanza, Innova $barayaInnova, HiAce $barayaHiace\n"
        . "Format Jawaban WhatsApp: Jawab secara ringkas (1-3 paragraf singkat), ramah, gunakan poin/emoji rapi khas WhatsApp, lalu tanyakan: 'Rencana perjalanannya untuk tanggal dan jam berapa Kak? Serta alamat jemput dan tujuannya ke mana agar Hasna siapkan unit terbaiknya 😊'.";

    $order = ($activeProvider === 'groq') ? ['groq', 'gemini'] : ['gemini', 'groq'];

    foreach ($order as $prov) {
        if ($prov === 'gemini' && $geminiKey) {
            $model = $aiConfig['gemini_model'] ?? 'gemini-2.5-flash';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($geminiKey);
            $fullPrompt = $systemContext . "\n\nPertanyaan Calon Tamu WhatsApp (" . ($senderName ?: 'Tamu') . "): " . $incomingMsg;
            $payloadGemini = json_encode([
                'contents' => [
                    ['parts' => [['text' => $fullPrompt]]]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 600
                ]
            ]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payloadGemini,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 9,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $res) {
                $parsed = json_decode($res, true);
                $text = $parsed['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if ($text) {
                    $aiReply = trim($text);
                    $sourceEngine = "Gemini ($model)";
                    break;
                }
            }
        } elseif ($prov === 'groq' && $groqKey) {
            $model = $aiConfig['groq_model'] ?? 'llama-3.3-70b-versatile';
            $url = "https://api.groq.com/openai/v1/chat/completions";
            $payloadGroq = json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemContext],
                    ['role' => 'user', 'content' => $incomingMsg]
                ],
                'temperature' => 0.7,
                'max_tokens' => 600
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
                CURLOPT_TIMEOUT => 9,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $res) {
                $parsed = json_decode($res, true);
                $text = $parsed['choices'][0]['message']['content'] ?? '';
                if ($text) {
                    $aiReply = trim($text);
                    $sourceEngine = "Groq ($model)";
                    break;
                }
            }
        }
    }
}

// 5. FALLBACK CERDAS JIKA API CLOUD BELUM DIISI ATAU ERROR
if (!$aiReply) {
    $msgLower = strtolower($incomingMsg);
    if (preg_match('/(harga|tarif|biaya|berapa|ongkos)/', $msgLower)) {
        if (preg_match('/(bandara|soetta|halim|cgk)/', $msgLower)) {
            $aiReply = "Halo Kak! Salam hangat dari Mbak Hasna (CS Traveego) 😊\n\nUntuk antar-jemput Bandara (Soetta & Halim):\n🚗 Avanza/Veloz: $soettaAvanza (All-In)\n✨ Innova Reborn: $soettaInnova (All-In)\n🚐 HiAce Premio: $soettaHiace (All-In)\n\nSemua tarif sudah ALL-IN (Mobil privat + Sopir + Tol Bandara + BBM). 0% DP bayar di tujuan!\n\nRencana untuk penerbangan jam berapa dan tanggal berapa Kak?";
        } elseif (preg_match('/(baraya|bandung|cimahi)/', $msgLower)) {
            $aiReply = "Halo Kak! Hasna siap bantu drop-off rute favorit Jakarta &ndash; Bandung (Baraya) PP:\n🚗 Avanza: $barayaAvanza (All-In)\n✨ Innova Reborn: $barayaInnova (All-In)\n🚐 HiAce Premio: $barayaHiace (All-In)\n\nSudah termasuk Tol MBZ, Tol Cipularang, BBM, dan Sopir. Tanpa DP sepeserpun!\n\nUntuk penjemputan tanggal berapa dan titik jemputnya di mana Kak?";
        } else {
            $aiReply = "Halo Kak! Hasna siap bantu layanan sewa mobil privat drop-off sekali jalan:\n✈️ Bandara Soetta: Mulai $soettaAvanza\n🏙️ Dalam Kota: Mulai $kotaAvanza\n🛣️ Jakarta &ndash; Bandung: Mulai $barayaAvanza\n\nTarif sudah ALL-IN Bersih (Mobil + BBM + Tol + Sopir), bayar saat tiba di tujuan. Mau rute yang mana Kak?";
        }
    } else {
        $aiReply = "Halo Kak! Terima kasih sudah menghubungi Traveego. Saya Mbak Hasna, CS AI resmi Traveego 😊\n\nKami melayani sewa mobil privat drop-off antarkota (Bandara Soetta/Halim, Jabodetabek, dan Bandung Raya) dengan sistem ALL-IN (sudah termasuk Mobil + Sopir + BBM + Tol Penuh) dan TANPA DP (bayar tunai/transfer saat tiba di tujuan).\n\nAda rute perjalanan yang bisa Hasna siapkan armadanya untuk Kakak?";
    }
}

// 6. KIRIM BALASAN KE WHATSAPP PENGIRIM VIA API FONNTE
$sendResult = null;
if (!empty($fonnteToken)) {
    $ch = curl_init('https://api.fonnte.com/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'target' => $sender,
            'message' => $aiReply
        ],
        CURLOPT_HTTPHEADER => ["Authorization: $fonnteToken"],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $sendResult = curl_exec($ch);
    curl_close($ch);
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
