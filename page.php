<?php
/**
 * TRAVEEGO DYNAMIC LANDING PAGE RENDERER
 * Menampilkan Landing Page Rute hasil racikan Bung Kevin (Page Crew)
 * Mendukung Auto-Scheduled sesuai Kalender Konten Mbak Dinda.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/yoast_analyzer.php';

date_default_timezone_set('Asia/Jakarta');

$slug = trim($_GET['slug'] ?? '');
$slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);

if (empty($slug)) {
    header("Location: /");
    exit;
}

$pageFile = __DIR__ . '/data/generated_pages/' . $slug . '.json';
if (!file_exists($pageFile)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='utf-8'><title>404 Halaman Tidak Ditemukan | Traveego</title><meta name='viewport' content='width=device-width, initial-scale=1'><style>body{font-family:sans-serif; text-align:center; padding:50px; background:#F8FAFC; color:#0F172A;} a{color:#0B4FD8; text-decoration:none; font-weight:700;}</style></head><body><h1>404</h1><p>Halaman rute yang Anda cari belum tersedia.</p><a href='/'>← Kembali ke Beranda Traveego</a></body></html>";
    exit;
}

$pageData = json_decode(file_get_contents($pageFile), true) ?: [];
$yoastAnalysis = YoastSeoAnalyzer::analyze($pageData);

// Load Data Kalender Mbak Dinda untuk validasi Auto-Scheduled
$calFile = __DIR__ . '/data/content_calendar.json';
$calendar = file_exists($calFile) ? (json_decode(file_get_contents($calFile), true) ?: []) : [];
$scheduleList = $calendar['schedule'] ?? [];

$publishAt = null;
$scheduleInfo = null;
foreach ($scheduleList as $item) {
    if (($item['slug'] ?? '') === $slug) {
        $publishAt = $item['publish_at'] ?? null;
        $scheduleInfo = $item;
        break;
    }
}

// Fallback tanggal publish dari file halaman jika tidak ada di kalender
if (!$publishAt && isset($pageData['publish_at'])) {
    $publishAt = $pageData['publish_at'];
}

$now = date('Y-m-d H:i:s');
$isScheduled = ($publishAt && $now < $publishAt);
$canPreview = isCrewLoggedIn() || isAdminLoggedIn();

// Jika belum waktunya terbit dan bukan admin, tampilkan mode "Terjadwal"
if ($isScheduled && !$canPreview) {
    http_response_code(200);
    $formattedDate = date('d F Y, \P\u\k\u\l H:i \W\I\B', strtotime($publishAt));
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Segera Hadir: <?= htmlspecialchars($pageData['meta_title'] ?? 'Layanan Drop Off Traveego') ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8FAFC; color: #0F172A; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 24px; box-sizing: border-box; text-align: center; }
            .box { background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 24px; max-width: 520px; padding: 40px 32px; box-shadow: 0 10px 30px rgba(15,23,42,0.06); }
            .badge { display: inline-flex; align-items: center; gap: 6px; background: #EFF6FF; color: #0B4FD8; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 800; margin-bottom: 20px; }
            h1 { font-size: 22px; font-weight: 800; margin: 0 0 12px; line-height: 1.4; color: #0F172A; }
            p { font-size: 14px; color: #64748B; line-height: 1.6; margin: 0 0 24px; }
            .schedule-pill { background: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; padding: 10px 16px; border-radius: 12px; font-size: 13px; font-weight: 700; margin-bottom: 24px; display: inline-block; }
            .btn { display: inline-flex; align-items: center; gap: 8px; background: #0B4FD8; color: #FFFFFF; text-decoration: none; padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 700; transition: all 0.2s; }
            .btn:hover { background: #1D4ED8; }
        </style>
    </head>
    <body>
        <div class="box">
            <div class="badge">⏳ KALENDER EDITORIAL TRAVEEGO</div>
            <h1>Halaman Sedang Dipersiapkan</h1>
            <p>Konten halaman penawaran rute ini telah selesai diracik oleh tim AI Traveego dan dijadwalkan terbit otomatis pada:</p>
            <div class="schedule-pill">🗓️ <?= $formattedDate ?></div>
            <div>
                <a href="/" class="btn">← Lihat Layanan Rute Aktif Lainnya</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Load Matrix Harga Terkini Pak Hendra
$matrixFile = __DIR__ . '/data/cost_matrix.json';
$matrix = file_exists($matrixFile) ? (json_decode(file_get_contents($matrixFile), true) ?: []) : [];
$routes = $matrix['routes'] ?? [];
$routeKey = $pageData['route_key'] ?? '';
$livePricing = $routes[$routeKey]['pricing'] ?? [];

$avanzaPrice = $livePricing['avanza']['formatted_price'] ?? ($pageData['pricing_cards'][0]['price'] ?? 'Rp 250.000');
$innovaPrice = $livePricing['innova']['formatted_price'] ?? ($pageData['pricing_cards'][1]['price'] ?? 'Rp 350.000');
$hiacePrice  = $livePricing['hiace']['formatted_price']  ?? ($pageData['pricing_cards'][2]['price'] ?? 'Rp 650.000');

$metaTitle = htmlspecialchars($pageData['meta_title'] ?? 'Sewa Mobil Drop Off All-In | Traveego');
$metaDesc = htmlspecialchars($pageData['meta_description'] ?? 'Layanan sewa mobil drop-off privat tarif All-In Tol dan BBM tanpa DP.');
$hero = $pageData['hero'] ?? [];
$headline = htmlspecialchars($hero['headline'] ?? 'SEWA MOBIL DROP OFF');
$subheadline = htmlspecialchars($hero['subheadline'] ?? 'Perjalanan privat nyaman All-In Tol + BBM tanpa DP.');
$waText = urlencode($hero['wa_text'] ?? "Halo Traveego, saya ingin pesan layanan Drop Off rute " . $slug);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $metaTitle ?></title>
    <meta name="description" content="<?= $metaDesc ?>">
    <link rel="canonical" href="https://dropoff.id/<?= htmlspecialchars($slug) ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Schema Markup -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Service",
      "serviceType": "Car Rental Drop-Off",
      "name": "<?= addslashes($metaTitle) ?>",
      "provider": {
        "@type": "LocalBusiness",
        "name": "Traveego Indonesia",
        "telephone": "+628190000000",
        "priceRange": "Rp 250.000 - Rp 1.500.000"
      },
      "description": "<?= addslashes($metaDesc) ?>",
      "offers": {
        "@type": "Offer",
        "priceCurrency": "IDR",
        "price": "<?= preg_replace('/[^0-9]/', '', $avanzaPrice) ?>",
        "availability": "https://schema.org/InStock"
      }
    }
    </script>

    <style>
        :root {
            --primary: #0B4FD8;
            --primary-dark: #073594;
            --accent-green: #16A34A;
            --bg-page: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-title: #0F172A;
            --text-body: #475569;
            --text-muted: #64748B;
            --border-subtle: #E2E8F0;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-page); color: var(--text-body); line-height: 1.6; }

        /* Preview Banner untuk Admin */
        <?php if ($isScheduled && $canPreview): ?>
        .admin-preview-bar {
            background: #FEF3C7;
            color: #92400E;
            text-align: center;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            border-bottom: 1px solid #FCD34D;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        <?php endif; ?>

        /* Header */
        .topbar {
            background: #FFFFFF;
            border-bottom: 1px solid var(--border-subtle);
            padding: 16px 24px;
            position: sticky;
            top: 0;
            z-index: 90;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-title);
            font-weight: 800;
            font-size: 18px;
        }
        .btn-topbar-wa {
            background: #16A34A;
            color: #FFFFFF;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-topbar-wa:hover { background: #15803D; }

        /* Container */
        .page-container { max-width: 1080px; margin: 0 auto; padding: 40px 20px 80px; }

        /* Hero */
        .hero-section {
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            border-radius: 24px;
            padding: 48px 36px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 6px;
            background: linear-gradient(90deg, #0B4FD8, #16A34A);
        }
        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #EFF6FF;
            color: #0B4FD8;
            border: 1px solid #BFDBFE;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 800;
            margin-bottom: 20px;
        }
        .hero-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-title);
            line-height: 1.3;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
        }
        .hero-desc {
            font-size: 16px;
            color: var(--text-body);
            max-width: 720px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }
        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #0B4FD8, #073594);
            color: #FFFFFF;
            padding: 16px 36px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 10px 25px rgba(11, 79, 216, 0.25);
            transition: all 0.2s;
        }
        .hero-cta:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(11, 79, 216, 0.35); }

        /* Trust Badges */
        .trust-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 40px;
        }
        .trust-card {
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        }
        .trust-icon { font-size: 28px; }
        .trust-text h4 { font-size: 14.5px; font-weight: 800; color: var(--text-title); margin-bottom: 2px; }
        .trust-text p { font-size: 12px; color: var(--text-muted); margin: 0; }

        /* Pricing Section */
        .pricing-section-title {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            color: var(--text-title);
            margin-bottom: 8px;
        }
        .pricing-section-sub {
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 32px;
        }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 50px;
        }
        .price-card {
            background: #FFFFFF;
            border: 1.5px solid var(--border-subtle);
            border-radius: 20px;
            padding: 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            position: relative;
        }
        .price-card.featured {
            border-color: #BFDBFE;
            background: linear-gradient(180deg, #FFFFFF, #F8FAFC);
            box-shadow: 0 8px 25px rgba(11, 79, 216, 0.08);
        }
        .featured-badge {
            position: absolute;
            top: -12px;
            right: 24px;
            background: #0B4FD8;
            color: #FFFFFF;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .price-vehicle { font-size: 18px; font-weight: 800; color: var(--text-title); margin-bottom: 6px; }
        .price-amount { font-size: 28px; font-weight: 800; color: var(--primary); margin-bottom: 16px; }
        .price-features { list-style: none; padding: 0; margin-bottom: 24px; font-size: 13.5px; }
        .price-features li { padding: 6px 0; border-bottom: 1px dashed #F1F5F9; color: var(--text-body); }
        .btn-card-book {
            display: block;
            text-align: center;
            background: #EFF6FF;
            color: #0B4FD8;
            border: 1.5px solid #BFDBFE;
            padding: 12px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
            transition: all 0.2s;
        }
        .btn-card-book:hover { background: #0B4FD8; color: #FFFFFF; }
        .price-card.featured .btn-card-book { background: #0B4FD8; color: #FFFFFF; border-color: #0B4FD8; }

        /* SOP Guarantee */
        .sop-card {
            background: #F0FDF4;
            border: 1.5px solid #BBF7D0;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
        }
        .sop-card h3 { font-size: 18px; font-weight: 800; color: #166534; margin-bottom: 12px; }
        .sop-card ul { padding-left: 20px; font-size: 13.5px; color: #15803D; line-height: 1.8; }

        /* Footer */
        .footer { text-align: center; padding: 30px; font-size: 13px; color: var(--text-muted); border-top: 1px solid var(--border-subtle); background: #FFFFFF; }

        @media (max-width: 800px) {
            .hero-title { font-size: 24px; }
            .trust-grid, .pricing-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php if ($isScheduled && $canPreview): ?>
<div class="admin-preview-bar">
    👁️ MODE PREVIEW ADMIN: Halaman ini dijadwalkan terbit otomatis pada <?= date('d M Y, H:i', strtotime($publishAt)) ?> WIB sesuai Kalender Mbak Dinda.
</div>
<?php endif; ?>

<header class="topbar">
    <a href="/" class="topbar-brand">
        <span style="font-size:24px;">🚐</span>
        <span>TRAVEEGO</span>
    </a>
    <a href="https://api.whatsapp.com/send?phone=628190000000&text=<?= $waText ?>" class="btn-topbar-wa" target="_blank" rel="noopener">
        <span>💬</span>
        <span>Pesan via WhatsApp (0% DP)</span>
    </a>
</header>

<main class="page-container">
    <!-- Hero -->
    <section class="hero-section">
        <div class="hero-pill">⚡ LAYANAN DROP OFF PRIVAT ALL-IN</div>
        <h1 class="hero-title"><?= $headline ?></h1>
        <p class="hero-desc"><?= $subheadline ?></p>
        <a href="https://api.whatsapp.com/send?phone=628190000000&text=<?= $waText ?>" class="hero-cta" target="_blank" rel="noopener">
            <span>🚀</span>
            <span>Pesan Unit Sekarang (0% DP &bull; Bayar di Tujuan)</span>
        </a>
    </section>

    <!-- Trust Badges -->
    <div class="trust-grid">
        <div class="trust-card">
            <div class="trust-icon">💵</div>
            <div class="trust-text">
                <h4>Tanpa Uang Muka (0% DP)</h4>
                <p>Bayar langsung ke sopir setelah Anda tiba dengan aman di tujuan.</p>
            </div>
        </div>
        <div class="trust-card">
            <div class="trust-icon">🛣️</div>
            <div class="trust-text">
                <h4>All-In Bersih Tanpa Tambahan</h4>
                <p>Tarif sudah termasuk Sewa Mobil, Jasa Sopir, Tol Penuh &amp; BBM.</p>
            </div>
        </div>
        <div class="trust-card">
            <div class="trust-icon">⏰</div>
            <div class="trust-text">
                <h4>Standby 30 Menit Awal</h4>
                <p>Armada tiba di titik penjemputan 30 menit sebelum jadwal perjalanan.</p>
            </div>
        </div>
    </div>

    <!-- Pricing Grid -->
    <h2 class="pricing-section-title">Pilihan Armada &amp; Tarif All-In Terverifikasi</h2>
    <p class="pricing-section-sub">Data tarif di bawah ini dihitung dan dipantau mingguan oleh sistem intelijen tarif Pak Hendra.</p>

    <div class="pricing-grid">
        <!-- Avanza -->
        <div class="price-card">
            <div class="price-vehicle">Toyota Avanza / Veloz</div>
            <div class="price-amount"><?= $avanzaPrice ?></div>
            <ul class="price-features">
                <li>✅ Kapasitas 3&ndash;4 Penumpang</li>
                <li>✅ Tol Penuh Golongan 1 Termasuk</li>
                <li>✅ BBM All-In Sesuai Rute</li>
                <li>✅ Penjemputan Door-to-Door</li>
                <li>✅ Bayar Cash / Transfer ke Sopir</li>
            </ul>
            <a href="https://api.whatsapp.com/send?phone=628190000000&text=<?= urlencode("Halo Traveego, saya ingin pesan Drop-Off unit Avanza untuk rute " . $slug) ?>" class="btn-card-book" target="_blank" rel="noopener">Pilih Avanza (0% DP)</a>
        </div>

        <!-- Innova Reborn -->
        <div class="price-card featured">
            <div class="featured-badge">Paling Favorit</div>
            <div class="price-vehicle">Toyota Innova Reborn</div>
            <div class="price-amount"><?= $innovaPrice ?></div>
            <ul class="price-features">
                <li>✅ Kapasitas 4&ndash;5 Penumpang</li>
                <li>✅ Suspensi Nyaman &amp; Kabin Kedap</li>
                <li>✅ Tol Penuh Golongan 1 Termasuk</li>
                <li>✅ BBM All-In Termasuk</li>
                <li>✅ Door-to-Door &amp; Tanpa Uang Muka</li>
            </ul>
            <a href="https://api.whatsapp.com/send?phone=628190000000&text=<?= urlencode("Halo Traveego, saya ingin pesan Drop-Off unit Innova Reborn untuk rute " . $slug) ?>" class="btn-card-book" target="_blank" rel="noopener">Pilih Innova Reborn (0% DP)</a>
        </div>

        <!-- Hiace Premio -->
        <div class="price-card">
            <div class="price-vehicle">Toyota HiAce Premio</div>
            <div class="price-amount"><?= $hiacePrice ?></div>
            <ul class="price-features">
                <li>✅ Kapasitas 8&ndash;10 Penumpang</li>
                <li>✅ Ruang Bagasi Luas Keluarga Besar</li>
                <li>✅ Tol Penuh Termasuk</li>
                <li>✅ BBM All-In Termasuk</li>
                <li>✅ Penjemputan Tepat Waktu 30 Menit Awal</li>
            </ul>
            <a href="https://api.whatsapp.com/send?phone=628190000000&text=<?= urlencode("Halo Traveego, saya ingin pesan Drop-Off unit HiAce Premio untuk rute " . $slug) ?>" class="btn-card-book" target="_blank" rel="noopener">Pilih HiAce Premio (0% DP)</a>
        </div>
    </div>

    <!-- SOP Bang Jaka -->
    <div class="sop-card">
        <h3>🛡️ Standar Pelayanan &amp; Keamanan Penumpang (SOP Armada Bang Jaka)</h3>
        <ul>
            <li><strong>Sopir Berpengalaman:</strong> Memahami rute tercepat via jalan tol tanpa ugal-ugalan.</li>
            <li><strong>Kabin Bersih &amp; Dingin:</strong> Unit disemprot disinfektan, wangi, ber-AC dingin, dan bebas dari aroma rokok.</li>
            <li><strong>Anti-Batal Sepihak:</strong> Penjemputan dijamin tepat waktu sesuai jam yang Anda pesan.</li>
        </ul>
    </div>
</main>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> Traveego Indonesia &bull; Layanan Drop Off Privat Door to Door All-In Tol &amp; BBM</p>
</footer>

<?php require __DIR__ . '/api/yoast_widget.php'; ?>

</body>
</html>
