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
    if (preg_match('/(hiace|premiere|commuter|rombongan)/', $message)) {
        $reply = "Untuk armada **Toyota HiAce Premio / Commuter (10-14 Kursi)** rute Jakarta - Bandung mulai dari **Rp 1.700.000 (All-In)** sudah termasuk Mobil + Sopir + BBM + Tol Cipularang penuh. Cocok sekali untuk rombongan keluarga besar Kak! 👍";
    } elseif (preg_match('/(innova|reborn|zenix)/', $message)) {
        $reply = "Tarif **Toyota Innova Reborn (Kenyamanan Eksekutif)** rute Jakarta - Bandung adalah **Rp 1.050.000 (All-In)**. Tarif sudah mencakup BBM, Tol MBZ/Cipularang, dan jasa sopir handal. Sangat empuk dan nyaman untuk perjalanan antarkota Kak!";
    } elseif (preg_match('/(avanza|xenia|veloz|hemat)/', $message)) {
        $reply = "Pilihan paling hemat **Toyota Avanza / Veloz (1-4 Penumpang)** tarifnya **Rp 750.000 (All-In)** untuk drop-off sekali jalan Jakarta - Bandung. Bebas biaya tambahan tersembunyi!";
    } else {
        $reply = "Tarif All-In sekali jalan Traveego:\n🚗 **Avanza/Veloz:** Rp 750.000\n✨ **Innova Reborn:** Rp 1.050.000\n🚐 **HiAce Premio:** Rp 1.700.000\n\nSemua tarif sudah **ALL-IN** (Mobil + Sopir + BBM + Tol Penuh)! Kakak cukup bayar cash ke sopir setelah sampai.";
    }
} elseif (preg_match('/(tol|bbm|bensin|all in|include|termasuk)/', $message)) {
    $reply = "Betul sekali Kak! Semua tarif di Traveego berkonsep **ALL-IN MURNI**:\n✅ Sudah termasuk Sewa Mobil\n✅ Jasa Pengemudi Profesional\n✅ BBM penuh\n✅ Saldo Tol penuh (Tol MBZ, Cipularang, Pasteur, dll)\n\nTidak ada biaya tambahan lagi di jalan. Sopir dilarang meminta tip atau uang bensin tambahan!";
} elseif (preg_match('/(bayar|pembayaran|cash|transfer|rekening)/', $message)) {
    $reply = "Sistem pembayaran kami sangat aman Kak! Kakak bayar **CASH LANGSUNG KE SOPIR** setelah kendaraan tiba dengan selamat di alamat tujuan Anda. Bebas rasa cemas dari penipuan uang muka!";
} elseif (preg_match('/(bandara|soetta|cgk|cengkareng)/', $message)) {
    $reply = "Kami melayani penjemputan dari **Bandara Internasional Soekarno-Hatta (Terminal 1, 2, atau 3)** langsung antar ke depan pintu rumah/hotel di Kota Bandung, Cimahi, atau Lembang. Sopir kami siap standby menunggu kedatangan pesawat Kakak!";
} elseif (preg_match('/(pesan|booking|order|cara)/', $message)) {
    $reply = "Cara pesannya sangat mudah Kak:\n1. Pilih rute perjalanan di form atas\n2. Masukkan alamat jemput & alamat tujuan lengkap\n3. Masukkan Nama dan WhatsApp Kakak\n4. Klik tombol hijau **'Pesan Sekarang'**\n\nOrderan Kakak akan langsung tayang ke radar lelang mitra sopir kami dalam hitungan detik!";
} elseif (preg_match('/(surabaya|malang|batu|jatim)/', $message)) {
    $reply = "Untuk koridor Jawa Timur, kami melayani rute **Surabaya / Bandara Juanda ke Kota Malang & Kota Wisata Batu** (via Tol Pandaan - Singosari) dengan tarif mulai Rp 650.000 All-In.";
} else {
    $reply = "Halo Kak! Tiara siap membantu perjalanan drop-off Kakak. Traveego menyediakan layanan sewa mobil antarkota sekali jalan (Jakarta - Bandung, Bandara Soetta, Surabaya - Malang) dengan sistem All-In (Tol + BBM + Sopir). Ada yang ingin Kakak tanyakan mengenai rute, pilihan mobil, atau cara pemesanan? 😊";
}

sendJson(['success' => true, 'reply' => $reply]);
