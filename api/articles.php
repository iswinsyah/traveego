<?php
/**
 * DROPOFF.ID API: DAFTAR ARTIKEL PUBLIKASI (BLOG POSTS)
 * Menyajikan artikel terbaru untuk ditampilkan di Homepage dan Blog Archive.
 * Dilengkapi Autonomous Web-Cron: Otomatis memproduksi artikel harian sesuai Kalender Mbak Dinda.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=60');

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/image_rotator.php';

// Endpoint monitoring kesehatan rotasi foto
if (isset($_GET['action']) && $_GET['action'] === 'pool_stats') {
    echo json_encode([
        'success' => true,
        'data' => ImageRotator::getPoolStats()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$dataDir = __DIR__ . '/../data';
$postsDir = $dataDir . '/generated_posts';
$calendarFile = $dataDir . '/content_calendar.json';
$matrixFile = $dataDir . '/cost_matrix.json';

if (!is_dir($postsDir)) {
    @mkdir($postsDir, 0755, true);
}

// =========================================================================
// AUTONOMOUS DAILY ARTICLE GENERATOR (SELF-TRIGGERING WEB-CRON)
// =========================================================================
function ensureDailyArticlesPublished($postsDir, $calendarFile, $matrixFile) {
    if (!file_exists($calendarFile)) return;

    $now = date('Y-m-d H:i:s');
    $calRaw = @file_get_contents($calendarFile);
    $calendar = json_decode($calRaw, true);
    if (!$calendar || empty($calendar['schedule'])) return;

    // Load Cost Matrix Pak Hendra untuk data harga riil
    $matrix = [];
    if (file_exists($matrixFile)) {
        $matrix = json_decode(@file_get_contents($matrixFile), true) ?: [];
    }

    $updatedAny = false;
    foreach ($calendar['schedule'] as &$item) {
        $publishAt = $item['publish_at'] ?? '2026-01-01 00:00:00';
        $slug = $item['slug'] ?? '';
        $type = $item['type'] ?? 'post';

        // Hanya generate post blog yang jadwalnya sudah tiba atau hari ini
        if ($type === 'post' && $slug && $publishAt <= $now) {
            $postFile = $postsDir . '/' . $slug . '.json';
            if (!file_exists($postFile)) {
                // Generate artikel Yoast SEO otomatis
                $newPost = generateAutomatedArticle($item, $matrix);
                @file_put_contents($postFile, json_encode($newPost, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $updatedAny = true;
            }
        }
    }
}

function generateAutomatedArticle($item, $matrix) {
    $title = $item['title'] ?? 'Panduan Layanan Sewa Drop Off All-In Dropoff.id';
    $slug = $item['slug'] ?? 'panduan-layanan-drop-off';
    $keyword = $item['seed_keyword'] ?? 'sewa drop off privat';
    $focusKw = ucwords($keyword);
    $publishAt = $item['publish_at'] ?? date('Y-m-d H:i:s');
    $category = 'Info Tarif & Perjalanan';

    // Rotator Cerdas Anti-Spam: Cooldown 90 hari (3 bulan), 100% foto alami non-AI
    $imgData = ImageRotator::getFeaturedImage($slug, $title, $category, $keyword);
    $featuredImg = $imgData['url'];

    $metaDesc = "Panduan lengkap {$title} resmi Dropoff.id. Layanan sewa mobil privat sekali jalan All-In BBM, Tol, dan garansi tanpa uang muka 0% DP.";

    $contentMd = "# {$title}\n\n"
        . "Mobilitas perjalanan darat kini semakin membutuhkan solusi yang praktis, cepat, dan transparan. "
        . "Layanan **{$keyword}** dari Dropoff.id hadir menjawab kebutuhan keluarga dan profesional yang menginginkan perjalanan privat tanpa repot. "
        . "Didukung oleh integrasi jaringan jalan tol modern dari [Badan Pengatur Jalan Tol (BPJT) PUPR](https://bpjt.pu.go.id/) "
        . "serta standar regulasi keselamatan angkutan darat dari [Kementerian Perhubungan Republik Indonesia](https://dephub.go.id/), perjalanan Anda dijamin aman dan nyaman.\n\n"
        . "Namun, masih banyak calon penumpang yang khawatir akan adanya biaya tersembunyi. Oleh karena itu, penting untuk memahami rincian layanan All-In sebelum memesan perjalanan Anda.\n\n"
        . "---\n\n"
        . "## 1. Keunggulan Sewa Drop Off Privat Dibandingkan Transportasi Konvensional\n\n"
        . "Mengapa tren sewa mobil sekali jalan (drop-off) kian populer? Pasalnya, layanan ini memberikan keleluasaan penuh bagi penumpang:\n\n"
        . "- **100% Mobil Privat Bebas Campur:** Kendaraan sepenuhnya untuk Anda dan rombongan sendiri, tanpa bercampur dengan penumpang lain yang tidak dikenal.\n"
        . "- **Penjemputan Door-to-Door:** Pengemudi menjemput tepat di depan lobi hotel, gerbang rumah, atau terminal bandara sesuai jadwal yang Anda tentukan.\n"
        . "- **Jaminan Bebas Uang Muka (0% DP):** Anda tidak perlu mentransfer uang muka di awal. Seluruh pembayaran dilakukan secara aman setelah tiba di tujuan.\n"
        . "- **Armada Terawat & Nyaman:** Didukung unit Toyota Avanza, Innova Reborn, dan HiAce Premio yang bersih dengan AC dingin.\n\n"
        . "Dengan demikian, Anda dapat beristirahat santai tanpa beban mengemudi di tengah kepadatan lalu lintas jalan tol.\n\n"
        . "---\n\n"
        . "## 2. Rincian Paket All-In Tanpa Biaya Tersembunyi\n\n"
        . "Salah satu komitmen utama Dropoff.id adalah transparansi harga mutlak. Setiap pemesanan layanan {$keyword} sudah mencakup:\n\n"
        . "1. **Bahan Bakar Minyak (BBM):** Termasuk bensin/solar dari titik jemput hingga lokasi antar tujuan.\n"
        . "2. **Tiket Jalan Tol:** Bebas biaya tambahan e-toll di sepanjang rute utama.\n"
        . "3. **Jasa & Konsumsi Sopir:** Bebas biaya makan supir, parkir penjemputan, atau uang tips wajib.\n\n"
        . "Oleh sebab itu, anggaran perjalanan yang disepakati di awal adalah total biaya yang Anda bayarkan kepada sopir.\n\n"
        . "---\n\n"
        . "## 3. Pilihan Armada Sesuai Kebutuhan Anda\n\n"
        . "Kami menyiapkan armada unggulan yang selalu siap beroperasi 24 jam:\n\n"
        . "- **Toyota All New Avanza (4–5 Seat):** Solusi hemat dan lincah untuk keluarga inti dengan barang bawaan standar.\n"
        . "- **Toyota Innova Reborn (6–7 Seat):** Rekomendasi perjalanan jauh dengan suspensi lembut, kabin senyap, dan bagasi luas.\n"
        . "- **Toyota HiAce Premio (10–12 Seat):** Kapasitas besar dengan ruang kaki ekstra nyaman untuk rombongan keluarga besar atau dinas kantor.\n\n"
        . "---\n\n"
        . "## 4. Panduan Reservasi Cepat Tanpa Ribet\n\n"
        . "Untuk memesan perjalanan, Anda cukup menghubungi layanan pelanggan kami dengan menentukan titik penjemputan dan waktu keberangkatan. "
        . "Sebagai contoh, untuk perjalanan bandara subuh atau drop-off antar kota, Anda dapat membaca [Panduan Biaya & Tarif Tol Jakarta ke Bandung](/blog/panduan-tarif-drop-off-jakarta-ke-bandung) "
        . "atau memeriksa [Layanan Drop Off Bandara Soekarno-Hatta](/drop-off-bandara-soekarno-hatta) untuk informasi armada terkini.\n\n"
        . "Selain itu, Anda dapat berkonsultasi langsung secara interaktif dengan CS AI Mbak Hasna di tombol WhatsApp untuk konfirmasi instan.";

    return [
        'slug' => $slug,
        'focus_keyword' => $focusKw,
        'title' => $title,
        'category' => $category,
        'published_at' => $publishAt,
        'target_route_slug' => 'drop-off-dalam-kota-jabodetabek',
        'featured_image' => $featuredImg,
        'meta_description' => $metaDesc,
        'content_markdown' => $contentMd
    ];
}

// Jalankan auto-publisher jika ada jadwal yang jatuh tempo
ensureDailyArticlesPublished($postsDir, $calendarFile, $matrixFile);

// =========================================================================
// BACA SELURUH ARTIKEL PUBLIKASI
// =========================================================================
$articles = [];
if (is_dir($postsDir)) {
    $files = glob($postsDir . '/*.json');
    foreach ($files as $file) {
        $raw = @file_get_contents($file);
        if ($raw) {
            $data = json_decode($raw, true);
            if ($data && !empty($data['slug']) && !empty($data['title'])) {
                $articles[] = [
                    'slug' => $data['slug'],
                    'title' => $data['title'],
                    'category' => $data['category'] ?? 'Info Perjalanan',
                    'published_at' => $data['published_at'] ?? date('Y-m-d H:i:s'),
                    'meta_description' => $data['meta_description'] ?? '',
                    'featured_image' => $data['featured_image'] ?? '',
                    'url' => '/blog/' . $data['slug']
                ];
            }
        }
    }
}

// Urutkan dari yang terbaru (Descending berdasarkan tanggal rilis)
usort($articles, function ($a, $b) {
    return strtotime($b['published_at']) - strtotime($a['published_at']);
});

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 6;
if ($limit > 0) {
    $articles = array_slice($articles, 0, $limit);
}

echo json_encode([
    'success' => true,
    'total' => count($articles),
    'articles' => $articles
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
