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
