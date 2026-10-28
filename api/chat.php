<?php
require_once __DIR__ . '/../config.php';

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$message = strtolower(trim($input['message'] ?? ''));

// Load data matrix tarif terbaru hasil riset mingguan Pak Hendra
$matrixFile = __DIR__ . '/../data/cost_matrix.json';
$matrix = [];
if (file_exists($matrixFile)) {
    $matrix = json_decode(file_get_contents($matrixFile), true) ?? [];
}
$routes = $matrix['routes'] ?? [];

// Helper untuk mengambil harga terupdate Pak Hendra
$soettaPrices = $routes['jakarta_bandara_soetta']['pricing'] ?? [];
$halimPrices  = $routes['jakarta_bandara_halim']['pricing'] ?? [];
$jabodetabekPrices = $routes['jabodetabek_dalam_kota']['pricing'] ?? [];
$barayaPrices = $routes['jakarta_baraya']['pricing'] ?? [];

$soettaAvanza = $soettaPrices['avanza']['formatted_price'] ?? 'Rp 250.000';
$soettaInnova = $soettaPrices['innova']['formatted_price'] ?? 'Rp 325.000';
$soettaHiace  = $soettaPrices['hiace']['formatted_price'] ?? 'Rp 400.000';

$barayaAvanza = $barayaPrices['avanza']['formatted_price'] ?? 'Rp 500.000';
$barayaInnova = $barayaPrices['innova']['formatted_price'] ?? 'Rp 700.000';
$barayaHiace  = $barayaPrices['hiace']['formatted_price'] ?? 'Rp 1.400.000';

$kotaAvanza = $jabodetabekPrices['avanza']['formatted_price'] ?? 'Rp 275.000';
$kotaInnova = $jabodetabekPrices['innova']['formatted_price'] ?? 'Rp 350.000';
$kotaHiace  = $jabodetabekPrices['hiace']['formatted_price'] ?? 'Rp 550.000';

// Info kesiapan armada dari Bang Jaka
$fleetStatus = "🚐 **Status Armada Standby (Bang Jaka):** Unit Avanza, Innova Reborn, dan HiAce Premio sedang standby di pool terdekat se-Jabodetabek & Bandung. Sopir siap meluncur jemput tepat waktu (standby 30 menit sebelum jadwal).";

if (empty($message)) {
    sendJson(['reply' => "Halo Kak! Tiara (CS AI Traveego) siap membantu. Mau drop-off ke Bandara Soetta/Halim, Dalam Kota Jabodetabek, atau rute Jakarta - Bandung (Baraya)? 😊\n\n$fleetStatus"]);
}

// ==============================================================
// 1. CLOUD LLM ENGINE (GEMINI & GROQ FAILOVER) UNTUK MBAK TIARA
// ==============================================================
$aiConfigFile = __DIR__ . '/../data/ai_server_config.json';
$aiConfig = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];

$geminiKey = trim($aiConfig['gemini_api_key'] ?? '');
$groqKey = trim($aiConfig['groq_api_key'] ?? '');
$activeProvider = $aiConfig['active_cloud_provider'] ?? 'gemini';

if (!empty($message) && ($geminiKey || $groqKey)) {
    $systemContext = "Kamu adalah Mbak Tiara, Customer Service AI resmi Traveego (sewa mobil privat drop-off sekali jalan antarkota).\n"
        . "Karakter: Ramah, santun, solutif, persuasif, memakai sapaan hangat ('Halo Kak!', 'Ada yang bisa Tiara bantu?').\n"
        . "Prinsip Utama Traveego:\n"
        . "1. TARIF ALL-IN: Sudah termasuk Sewa Mobil Privat + Jasa Sopir + BBM + Saldo Tol Penuh (Tol MBZ, Cipularang, Sedyatmo, JORR).\n"
        . "2. PEMBAYARAN AMAN (0% DP): Tidak ada uang muka sama sekali. Pembayaran cash atau transfer langsung ke sopir setelah tiba di tujuan.\n"
        . "3. KOORDINASI ARMADA (BANG JAKA): Unit standby di pool terdekat se-Jabodetabek & Bandung (Avanza 1-4 pax, Innova Reborn 1-5 pax, HiAce Premio 8-14 pax). Sopir standby 30 menit sebelum jadwal penjemputan door-to-door.\n"
        . "4. TARIF ACUAN RESMI PAK HENDRA:\n"
        . "   - Bandara Soetta/Halim: Avanza $soettaAvanza, Innova $soettaInnova, HiAce $soettaHiace\n"
        . "   - Dalam Kota Jabodetabek: Avanza $kotaAvanza, Innova $kotaInnova, HiAce $kotaHiace\n"
        . "   - Jakarta ke Bandung (Baraya): Avanza $barayaAvanza, Innova $barayaInnova, HiAce $barayaHiace\n"
        . "Jawab pertanyaan calon pelanggan secara ringkas (maksimal 2-3 paragraf), jelas, sopan, dan ajak untuk klik tombol 'Pesan Cepat' di web.";

    $aiReply = null;
    $providerUsed = '';

    // Prioritas provider (Primary vs Backup)
    $order = ($activeProvider === 'groq') ? ['groq', 'gemini'] : ['gemini', 'groq'];

    foreach ($order as $prov) {
        if ($prov === 'gemini' && $geminiKey) {
            $model = $aiConfig['gemini_model'] ?? 'gemini-2.5-flash';
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($geminiKey);
            $fullPrompt = $systemContext . "\n\nPertanyaan Tamu: " . ($input['message'] ?? '');
            $payload = json_encode([
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
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 6,
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
                    $providerUsed = 'Google Gemini (' . $model . ')';
                    break;
                }
            }
        } elseif ($prov === 'groq' && $groqKey) {
            $model = $aiConfig['groq_model'] ?? 'llama-3.3-70b-versatile';
            $url = "https://api.groq.com/openai/v1/chat/completions";
            $payload = json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemContext],
                    ['role' => 'user', 'content' => $input['message'] ?? '']
                ],
                'temperature' => 0.7,
                'max_tokens' => 600
            ]);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $groqKey
                ],
                CURLOPT_TIMEOUT => 6,
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
                    $providerUsed = 'Groq Cloud (' . $model . ')';
                    break;
                }
            }
        }
    }

    if ($aiReply) {
        sendJson([
            'success' => true,
            'reply' => $aiReply,
            'source' => $providerUsed
        ]);
    }
}

// ==============================================================
// 2. FALLBACK DETERMINISTIK / ATURAN MATRIKS PAK HENDRA
// ==============================================================
$reply = '';

if (preg_match('/(harga|tarif|biaya|berapa|ongkos)/', $message)) {
    if (preg_match('/(bandara|soetta|halim|cgk|hlp)/', $message)) {
        $reply = "Untuk layanan **Antar Jemput Bandara (Soekarno-Hatta & Halim)** se-Jabodetabek (Data Terverifikasi Pak Hendra):\n🚗 **Avanza/Veloz:** $soettaAvanza (All-In)\n✨ **Innova Reborn:** $soettaInnova (All-In)\n🚐 **HiAce Premio:** $soettaHiace (All-In)\n\nSudah termasuk Mobil Privat + Sopir Standby + Tol Bandara + BBM. Sopir Bang Jaka siap standby di titik jemput 30 menit sebelum jadwal!";
    } elseif (preg_match('/(dalam kota|jabodetabek|bogor|depok|tangerang|bekasi|jakarta)/', $message)) {
        $reply = "Untuk layanan **Drop-Off Dalam Kota Jabodetabek (Point-to-Point)**:\n🚗 **Avanza/Veloz:** $kotaAvanza (All-In)\n✨ **Innova Reborn:** $kotaInnova (All-In)\n🚐 **HiAce Premio:** $kotaHiace (All-In)\n\nSudah ALL-IN (Mobil privat + Jasa Sopir + BBM + Tol Dalam Kota). Sangat praktis tanpa perlu oper kendaraan! 0% DP bayar di tujuan.";
    } elseif (preg_match('/(baraya|bandung|cimahi|lembang|soreang)/', $message)) {
        $reply = "Untuk rute favorit **Jakarta / Jabodetabek ke Baraya (Bandung Raya)**:\n🚗 **Avanza/Veloz:** $barayaAvanza (All-In)\n✨ **Innova Reborn:** $barayaInnova (All-In)\n🚐 **HiAce Premio:** $barayaHiace (All-In)\n\nSemua paket sudah ALL-IN bersih mencakup Tol MBZ penuh, Tol Cipularang, BBM, dan Sopir! Tanpa DP sepeserpun, bayar ke sopir saat tiba.";
    } else {
        $reply = "Saat ini Traveego melayani 3 fokus rute utama (Update Tarif Pak Hendra):\n1. ✈️ **Antar Jemput Bandara (Soetta & Halim)**: Mulai $soettaAvanza\n2. 🏙️ **Drop-Off Dalam Kota Jabodetabek**: Mulai $kotaAvanza\n3. 🛣️ **Jakarta &ndash; Baraya (Bandung Raya)**: Mulai $barayaAvanza\n\nSemua tarif berkonsep **ALL-IN MURNI** (Mobil + Sopir + BBM + Tol), tanpa DP, bayar langsung ke sopir saat tiba di tujuan!\n\n$fleetStatus";
    }
} elseif (preg_match('/(armada|mobil|avanza|innova|hiace|ready|standby|unit)/', $message)) {
    $reply = "Koordinasi Tiara langsung dengan **Bang Jaka (Dispatcher Armada)**:\n\n$fleetStatus\n\n📌 **Pilihan Armada:**\n1. **Toyota Avanza / Veloz** (3-4 Pax) – Nyaman, irit, lincah.\n2. **Toyota Innova Reborn** (4-5 Pax) – Suspensi empuk, bagasi luas, premium.\n3. **Toyota HiAce Premio** (8-10 Pax) – Kapasitas besar untuk rombongan & keluarga besar.\n\nSemua unit wangi, bersih, AC dingin, dan bebas asap rokok.";
} elseif (preg_match('/(tol|bbm|bensin|all in|include|termasuk)/', $message)) {
    $reply = "Betul sekali Kak! Semua tarif di Traveego berkonsep **ALL-IN MURNI** sesuai kalkulasi Pak Hendra:\n✅ Sewa Mobil Privat (Avanza / Innova / HiAce)\n✅ Jasa Sopir Profesional\n✅ BBM penuh\n✅ Saldo Tol penuh (Tol MBZ, Tol Cipularang, Tol Bandara Sedyatmo, JORR)\n\nTidak ada biaya tersembunyi. 0% DP, bayar tunai/transfer ke sopir setelah sampai tujuan.";
} elseif (preg_match('/(bayar|pembayaran|cash|transfer|rekening|dp)/', $message)) {
    $reply = "Sistem pembayaran Traveego 100% aman Kak! **TANPA UANG MUKA (0% DP)**. Kakak bayar **CASH ATAU TRANSFER LANGSUNG KE SOPIR** setelah kendaraan tiba dengan selamat di alamat tujuan Anda. Bebas rasa cemas dari penipuan!";
} elseif (preg_match('/(bandara|soetta|cgk|cengkareng|halim)/', $message)) {
    $reply = "Kami melayani antar jemput dari **Bandara Soekarno-Hatta (Terminal 1, 2, atau 3)** serta **Bandara Halim Perdanakusuma** ke seluruh Jabodetabek dan Baraya (Bandung Raya). Bang Jaka memastikan sopir standby di pick-up zone memantau nomor penerbangan Kakak secara real-time!";
} elseif (preg_match('/(semarang|surabaya|solo|jogja|luar kota)/', $message)) {
    $reply = "Untuk saat ini, demi menjaga garansi armada prima dan ketepatan waktu penjemputan Bang Jaka, Traveego memfokuskan armada khusus pada koridor **Antar Jemput Bandara (Soetta & Halim)**, **Dalam Kota Jabodetabek**, dan **Jakarta &ndash; Baraya (Bandung Raya)** Kak. 😊";
} else {
    $reply = "Halo Kak! Tiara (CS AI Traveego) siap membantu. Kami melayani rute prioritas: ✈️ **Bandara (Soetta & Halim)**, 🏙️ **Dalam Kota Jabodetabek**, dan 🛣️ **Jakarta &ndash; Baraya (Bandung Raya)** dengan sistem All-In (Tol + BBM + Sopir) tanpa DP.\n\n$fleetStatus\n\nAda rute yang ingin Kakak tanyakan tarifnya?";
}

sendJson(['success' => true, 'reply' => $reply]);
