<?php
require_once __DIR__ . '/../config.php';

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$message = strtolower(trim($input['message'] ?? ''));

if (empty($message)) {
    sendJson(['reply' => 'Halo Kak! Ada yang bisa Tiara bantu seputar drop-off Jakarta - Bandung, Bandara Soetta, atau Surabaya - Malang? 😊']);
}

// Logic cerdas berbasis kata kunci (Zero-Cost, Cepat, Tanpa kuota token habis)
$reply = '';

if (preg_match('/(harga|tarif|biaya|berapa|ongkos)/', $message)) {
    if (preg_match('/(bandara|soetta|halim|cgk|hlp)/', $message)) {
        $reply = "Untuk layanan **Antar Jemput Bandara (Soekarno-Hatta & Halim)** se-Jabodetabek:\n🚗 **Avanza/Veloz:** Mulai Rp 250.000 (All-In)\n✨ **Innova Reborn:** Mulai Rp 350.000 (All-In)\n🚐 **HiAce:** Mulai Rp 650.000 (All-In)\n\nSudah termasuk Mobil + Sopir Standby + Tol Bandara + BBM. Sopir siap jemput tepat waktu sesuai jadwal penerbangan Kakak! ✈️";
    } elseif (preg_match('/(dalam kota|jabodetabek|bogor|depok|tangerang|bekasi|jakarta)/', $message)) {
        $reply = "Untuk layanan **Drop-Off Dalam Kota Jabodetabek (Point-to-Point)**:\n🚗 **Avanza/Veloz:** Rp 275.000 (All-In)\n✨ **Innova Reborn:** Rp 375.000 (All-In)\n🚐 **HiAce Premio:** Rp 700.000 (All-In)\n\nSudah ALL-IN (Mobil privat + Sopir + BBM + Tol). Sangat praktis tanpa perlu oper kendaraan!";
    } elseif (preg_match('/(baraya|bandung|cimahi|lembang|soreang)/', $message)) {
        $reply = "Untuk rute favorit **Jakarta / Jabodetabek ke Baraya (Bandung Raya)**:\n🚗 **Avanza/Veloz:** Rp 500.000 (All-In)\n✨ **Innova Reborn:** Rp 750.000 (All-In)\n🚐 **HiAce Premio:** Rp 1.450.000 (All-In)\n\nSemua paket sudah ALL-IN bersih mencakup Tol MBZ penuh, Tol Cipularang, BBM, dan Sopir! 0% DP, bayar di lokasi.";
    } else {
        $reply = "Saat ini Traveego melayani 3 fokus rute utama:\n1. ✈️ **Antar Jemput Bandara (Soetta & Halim)**: Mulai Rp 250.000\n2. 🏙️ **Drop-Off Dalam Kota Jabodetabek**: Mulai Rp 275.000\n3. 🛣️ **Jakarta &ndash; Baraya (Bandung Raya)**: Mulai Rp 500.000\n\nSemua tarif berkonsep **ALL-IN MURNI** (Mobil + Sopir + BBM + Tol), tanpa DP, bayar langsung ke sopir saat tiba!";
    }
} elseif (preg_match('/(tol|bbm|bensin|all in|include|termasuk)/', $message)) {
    $reply = "Betul sekali Kak! Semua tarif di Traveego berkonsep **ALL-IN MURNI**:\n✅ Sudah termasuk Sewa Mobil Privat\n✅ Jasa Sopir Profesional\n✅ BBM penuh\n✅ Saldo Tol penuh (Tol MBZ, Tol Bandara, Tol Dalam Kota/Cipularang)\n\nTidak ada biaya tambahan lagi di jalan. Tanpa DP, bayar tunai/transfer ke sopir saat tiba.";
} elseif (preg_match('/(bayar|pembayaran|cash|transfer|rekening)/', $message)) {
    $reply = "Sistem pembayaran kami sangat aman Kak! Kakak bayar **CASH / TRANSFER LANGSUNG KE SOPIR** setelah kendaraan tiba dengan selamat di alamat tujuan Anda. Bebas rasa cemas dari penipuan uang muka (0% DP)!";
} elseif (preg_match('/(bandara|soetta|cgk|cengkareng|halim)/', $message)) {
    $reply = "Kami melayani penjemputan dari **Bandara Soekarno-Hatta (Terminal 1, 2, atau 3)** serta **Bandara Halim Perdanakusuma** ke seluruh penjuru Jabodetabek dan Baraya (Bandung Raya). Sopir kami siap standby memantau jadwal pendaratan pesawat Kakak!";
} elseif (preg_match('/(semarang|surabaya|solo|jogja|luar kota)/', $message)) {
    $reply = "Untuk saat ini, demi menjaga kualitas armada dan ketepatan waktu penjemputan, Traveego memfokuskan armada khusus pada koridor **Antar Jemput Bandara (Soetta & Halim)**, **Dalam Kota Jabodetabek**, dan **Jakarta &ndash; Baraya (Bandung Raya)** Kak. 😊";
} else {
    $reply = "Halo Kak! Tiara siap membantu perjalanan drop-off Kakak. Traveego saat ini melayani rute prioritas: ✈️ **Bandara (Soetta & Halim)**, 🏙️ **Dalam Kota Jabodetabek**, dan 🛣️ **Jakarta &ndash; Baraya (Bandung Raya)** dengan sistem All-In (Tol + BBM + Sopir) tanpa DP. Ada yang ingin Kakak tanyakan?";
}

sendJson(['success' => true, 'reply' => $reply]);
