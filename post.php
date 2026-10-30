<?php
/**
 * DROPOFF.ID DYNAMIC BLOG POST RENDERER
 * Menampilkan Artikel Blog SEO hasil karya Mbak Maya (Post Crew)
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

$postFile = __DIR__ . '/data/generated_posts/' . $slug . '.json';
if (!file_exists($postFile)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='utf-8'><title>404 Artikel Tidak Ditemukan | Dropoff.id Blog</title><meta name='viewport' content='width=device-width, initial-scale=1'><style>body{font-family:sans-serif; text-align:center; padding:50px; background:#F8FAFC; color:#0F172A;} a{color:#0B4FD8; text-decoration:none; font-weight:700;}</style></head><body><h1>404</h1><p>Artikel blog yang Anda cari belum tersedia.</p><a href='/'>← Kembali ke Beranda Dropoff.id</a></body></html>";
    exit;
}

$postData = json_decode(file_get_contents($postFile), true) ?: [];
$yoastAnalysis = YoastSeoAnalyzer::analyze($postData);

// Load Nomor CS AI WhatsApp Resmi (Mbak Hasna)
$siteContentFile = __DIR__ . '/data/site_content.json';
$siteContent = file_exists($siteContentFile) ? (json_decode(file_get_contents($siteContentFile), true) ?: []) : [];
$csAiPhone = $siteContent['head']['hotline_wa'] ?? ($siteContent['hero']['wa_number'] ?? '6281234567891');

// Load Data Kalender Mbak Dinda untuk validasi Auto-Scheduled
$calFile = __DIR__ . '/data/content_calendar.json';
$calendar = file_exists($calFile) ? (json_decode(file_get_contents($calFile), true) ?: []) : [];
$scheduleList = $calendar['schedule'] ?? [];

$publishAt = null;
foreach ($scheduleList as $item) {
    if (($item['slug'] ?? '') === $slug) {
        $publishAt = $item['publish_at'] ?? null;
        break;
    }
}

if (!$publishAt && isset($postData['published_at'])) {
    $publishAt = $postData['published_at'];
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
        <title>Terjadwal: <?= htmlspecialchars($postData['title'] ?? 'Artikel Blog Dropoff.id') ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; background: #F8FAFC; color: #0F172A; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 24px; box-sizing: border-box; text-align: center; }
            .box { background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 24px; max-width: 520px; padding: 40px 32px; box-shadow: 0 10px 30px rgba(15,23,42,0.06); }
            .badge { display: inline-flex; align-items: center; gap: 6px; background: #EFF6FF; color: #0B4FD8; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 800; margin-bottom: 20px; }
            h1 { font-size: 20px; font-weight: 800; margin: 0 0 12px; line-height: 1.4; color: #0F172A; }
            p { font-size: 14px; color: #64748B; line-height: 1.6; margin: 0 0 24px; }
            .schedule-pill { background: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; padding: 10px 16px; border-radius: 12px; font-size: 13px; font-weight: 700; margin-bottom: 24px; display: inline-block; }
            .btn { display: inline-flex; align-items: center; gap: 8px; background: #0B4FD8; color: #FFFFFF; text-decoration: none; padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 700; }
        </style>
    </head>
    <body>
        <div class="box">
            <div class="badge">📅 JADWAL PUBLIKASI ARTIKEL</div>
            <h1>Artikel Sedang Dalam Antrean Tayang</h1>
            <p>Artikel edukasi perjalanan ini dijadwalkan rilis otomatis ke publik pada:</p>
            <div class="schedule-pill">🗓️ <?= $formattedDate ?></div>
            <div>
                <a href="/" class="btn">← Kembali ke Beranda</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Load Matrix Harga Terkini Pak Hendra untuk replace shortcode dinamis
$matrixFile = __DIR__ . '/data/cost_matrix.json';
$matrix = file_exists($matrixFile) ? (json_decode(file_get_contents($matrixFile), true) ?: []) : [];
$routes = $matrix['routes'] ?? [];

$rawMarkdown = $postData['content_markdown'] ?? '';

// Replace shortcodes dinamis dengan data riil Pak Hendra
foreach ($routes as $rKey => $rVal) {
    $rSlug = str_replace('-', '_', $rVal['slug']);
    $pricing = $rVal['pricing'] ?? [];

    $tolFormatted = 'Rp ' . number_format($rVal['toll_cost'], 0, ',', '.');
    $avanzaFormatted = $pricing['avanza']['formatted_price'] ?? 'Rp 250.000';
    $innovaFormatted = $pricing['innova']['formatted_price'] ?? 'Rp 350.000';
    $bbmFormatted = isset($pricing['avanza']['fuel_cost']) ? 'Rp ' . number_format($pricing['avanza']['fuel_cost'], 0, ',', '.') : 'Rp 60.000';

    $rawMarkdown = str_replace("{{tol_{$rSlug}}}", $tolFormatted, $rawMarkdown);
    $rawMarkdown = str_replace("{{harga_avanza_{$rSlug}}}", $avanzaFormatted, $rawMarkdown);
    $rawMarkdown = str_replace("{{harga_innova_{$rSlug}}}", $innovaFormatted, $rawMarkdown);
    $rawMarkdown = str_replace("{{bbm_avanza_{$rSlug}}}", $bbmFormatted, $rawMarkdown);
}

// Fallback shortcodes umum
$rawMarkdown = preg_replace('/\{\{tol_[^\}]+\}\}/', 'Rp 45.000 - Rp 140.000', $rawMarkdown);
$rawMarkdown = preg_replace('/\{\{harga_avanza_[^\}]+\}\}/', 'Rp 250.000 - Rp 500.000', $rawMarkdown);
$rawMarkdown = preg_replace('/\{\{harga_innova_[^\}]+\}\}/', 'Rp 325.000 - Rp 700.000', $rawMarkdown);
$rawMarkdown = preg_replace('/\{\{bbm_[^\}]+\}\}/', 'Rp 40.000 - Rp 85.000', $rawMarkdown);

// Smart Markdown to HTML parser dengan deteksi link keluar (Outbound) vs link ke dalam (Internal)
function parseInlineLinksAndFormatting($text) {
    // Replace links [text](url) dengan atribut SEO ramah Google
    $text = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function($matches) {
        $anchor = $matches[1];
        $url = $matches[2];
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            // Outbound Link (External): Buka tab baru, noopener noreferrer untuk otoritas & keamanan
            return '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer">' . $anchor . '</a>';
        } else {
            // Internal Link: Pindah halaman secara mulus
            return '<a href="' . htmlspecialchars($url) . '">' . $anchor . '</a>';
        }
    }, $text);

    // Replace bold **text**
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    return $text;
}

function markdownToHtml($md) {
    $lines = explode("\n", $md);
    $html = '';
    $inList = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            continue;
        }

        // Horizontal Rule
        if ($trimmed === '---' || $trimmed === '***') {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $html .= "<hr style='border: 0; border-top: 1px solid #E2E8F0; margin: 32px 0;'>\n";
            continue;
        }

        // Headings
        if (preg_match('/^### (.*)/', $trimmed, $m)) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $html .= "<h3>" . htmlspecialchars($m[1]) . "</h3>\n";
        } elseif (preg_match('/^## (.*)/', $trimmed, $m)) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $html .= "<h2>" . htmlspecialchars($m[1]) . "</h2>\n";
        } elseif (preg_match('/^# (.*)/', $trimmed, $m)) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $html .= "<h1>" . htmlspecialchars($m[1]) . "</h1>\n";
        } elseif (preg_match('/^> (.*)/', $trimmed, $m)) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $text = parseInlineLinksAndFormatting($m[1]);
            $html .= "<blockquote>" . $text . "</blockquote>\n";
        } elseif (preg_match('/^[-*] (.*)/', $trimmed, $m)) {
            if (!$inList) { $html .= "<ul>\n"; $inList = true; }
            $text = parseInlineLinksAndFormatting($m[1]);
            $html .= "<li>" . $text . "</li>\n";
        } else {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $text = parseInlineLinksAndFormatting($trimmed);
            $html .= "<p>" . $text . "</p>\n";
        }
    }
    if ($inList) { $html .= "</ul>\n"; }
    return $html;
}

$articleHtml = markdownToHtml($rawMarkdown);
$title = htmlspecialchars($postData['title'] ?? 'Panduan Tarif Drop Off Dropoff.id');
$metaDesc = htmlspecialchars($postData['meta_description'] ?? 'Panduan sewa mobil drop-off tarif all in tol dan bbm.');
$category = htmlspecialchars($postData['category'] ?? 'Info Tarif & Tol');
$publishedDate = date('d F Y', strtotime($publishAt ?: 'now'));
$featuredImage = $postData['featured_image'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | Dropoff.id Blog</title>
    <meta name="description" content="<?= $metaDesc ?>">
    <?php if (!empty($featuredImage)): ?><meta property="og:image" content="<?= htmlspecialchars($featuredImage) ?>"><?php endif; ?>
    <link rel="canonical" href="https://dropoff.id/blog/<?= htmlspecialchars($slug) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Schema Article -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BlogPosting",
      "headline": "<?= addslashes($title) ?>",
      "description": "<?= addslashes($metaDesc) ?>",
      "publisher": {
        "@type": "Organization",
        "name": "Dropoff.id Indonesia"
      },
      "publisher": {
        "@type": "Organization",
        "name": "Dropoff.id Indonesia"
      },
      "datePublished": "<?= date('c', strtotime($publishAt ?: 'now')) ?>",
      "dateModified": "<?= date('c', strtotime($publishAt ?: 'now')) ?>"
    }
    </script>

    <style>
        :root {
            --primary: #0B4FD8;
            --text-title: #0F172A;
            --text-body: #334155;
            --text-muted: #64748B;
            --border-subtle: #E2E8F0;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Plus Jakarta Sans', sans-serif; }
        body { background: #F8FAFC; color: var(--text-body); line-height: 1.7; }

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

        .topbar {
            background: #FFFFFF;
            border-bottom: 1px solid var(--border-subtle);
            padding: 16px 24px;
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
            font-size: 13px;
            font-weight: 700;
        }

        .article-container { max-width: 820px; margin: 0 auto; padding: 40px 20px 80px; }

        /* Meta Header */
        .category-pill {
            display: inline-block;
            background: #EFF6FF;
            color: #0B4FD8;
            font-size: 12px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 14px;
        }
        .article-title {
            font-size: 30px;
            font-weight: 800;
            color: var(--text-title);
            line-height: 1.35;
            margin-bottom: 16px;
            letter-spacing: -0.4px;
        }
        .author-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border-subtle);
            margin-bottom: 32px;
        }
        .author-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FEF3C7, #FDE68A);
            border: 2px solid #FCD34D;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .author-info h5 { font-size: 13.5px; font-weight: 800; color: var(--text-title); }
        .author-info p { font-size: 11.5px; color: var(--text-muted); margin: 0; }

        /* Article Body */
        .article-body {
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 40px 36px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
            font-size: 15.5px;
            color: #334155;
        }
        .article-body h1, .article-body h2, .article-body h3 {
            color: var(--text-title);
            font-weight: 800;
            margin-top: 32px;
            margin-bottom: 14px;
            line-height: 1.3;
        }
        .article-body h2 { font-size: 22px; border-bottom: 2px solid #EFF6FF; padding-bottom: 8px; }
        .article-body h3 { font-size: 18px; }
        .article-body p { margin-bottom: 18px; line-height: 1.75; }
        .article-body ul { margin-bottom: 20px; padding-left: 24px; }
        .article-body li { margin-bottom: 8px; line-height: 1.6; }
        .article-body strong { color: var(--text-title); }
        .article-body a { color: #0B4FD8; font-weight: 700; text-decoration: none; border-bottom: 1px solid #BFDBFE; }
        .article-body a:hover { color: #073594; }
        .article-body blockquote {
            background: #F0FDF4;
            border-left: 4px solid #16A34A;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 24px 0;
            font-size: 14.5px;
            color: #166534;
        }

        /* CTA Bottom High-Contrast */
        .cta-box {
            background: linear-gradient(145deg, #072D6B 0%, #0B4FD8 100%);
            border: 2px solid #60A5FA;
            border-radius: 20px;
            padding: 36px 28px;
            text-align: center;
            margin-top: 42px;
            box-shadow: 0 14px 35px -5px rgba(11, 79, 216, 0.4);
            position: relative;
            overflow: hidden;
        }
        .cta-box::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .cta-badge-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #FEF08A;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .cta-box h3 {
            font-size: 24px;
            font-weight: 800;
            color: #FDE047 !important; /* Kuning Emas Terang Kontras */
            margin-bottom: 12px;
            line-height: 1.35;
            text-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .cta-box p {
            font-size: 15.5px;
            color: #FFFFFF !important; /* Putih Bersih Kontras */
            line-height: 1.65;
            margin-bottom: 26px;
            max-width: 620px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 500;
            text-shadow: 0 1px 3px rgba(0,0,0,0.25);
        }
        .btn-cta-wa {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #16A34A; /* Hijau WhatsApp */
            color: #FFFFFF !important; /* Tulisannya Putih Kontras */
            padding: 15px 32px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(22, 163, 74, 0.45);
            transition: all 0.2s ease;
            border: 1.5px solid #86EFAC;
        }
        .btn-cta-wa:hover {
            background: #15803D;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.55);
            color: #FFFFFF !important;
        }
        .btn-cta-wa span {
            color: #FFFFFF !important;
        }

        .footer { text-align: center; padding: 30px; font-size: 13px; color: var(--text-muted); border-top: 1px solid var(--border-subtle); background: #FFFFFF; }

        @media (max-width: 600px) {
            .article-title { font-size: 22px; }
            .article-body { padding: 24px 18px; }
        }
    </style>
</head>
<body>

<?php if ($isScheduled && $canPreview): ?>
<div class="admin-preview-bar">
    👁️ MODE PREVIEW ADMIN: Artikel ini dijadwalkan terbit otomatis pada <?= date('d M Y, H:i', strtotime($publishAt)) ?> WIB sesuai Kalender Mbak Dinda.
</div>
<?php endif; ?>

<header class="topbar">
    <a href="/" class="topbar-brand">
        <span style="font-size:24px;">🚐</span>
        <span>DROPOFF.ID BLOG</span>
    </a>
    <a href="https://wa.me/<?= $csAiPhone ?>?text=<?= urlencode('Halo Admin Dropoff.id, saya sedang membaca artikel: ' . $title) ?>" class="btn-topbar-wa" target="_blank" rel="noopener">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; flex-shrink: 0;"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
        <span>Hubungi Customer Service</span>
    </a>
</header>

<main class="article-container">
    <span class="category-pill"><?= $category ?></span>
    <h1 class="article-title"><?= $title ?></h1>

    <div style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 13.5px; padding-bottom: 20px; margin-bottom: 28px; border-bottom: 1px solid var(--border-subtle);">
        <span>📅 Diterbitkan <?= $publishedDate ?></span>
        <span>&bull;</span>
        <span>⏱️ 4 Menit Baca</span>
        <span>&bull;</span>
        <span>🛡️ Panduan Resmi Dropoff.id</span>
    </div>

    <?php if (!empty($featuredImage)): ?>
    <div style="margin-bottom: 30px; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06);">
        <img src="<?= htmlspecialchars($featuredImage) ?>" alt="<?= htmlspecialchars($title) ?>" style="width: 100%; height: auto; max-height: 440px; object-fit: cover; display: block;" loading="lazy">
    </div>
    <?php endif; ?>

    <article class="article-body">
        <?= $articleHtml ?>

        <div class="cta-box">
            <div class="cta-badge-tag">⚡ Layanan Siaga 24 Jam &bull; 0% Uang Muka</div>
            <h3>Mau Bepergian Tanpa Capek Menyetir?</h3>
            <p>Nikmati layanan sewa mobil Drop-Off privat Dropoff.id. Tarif All-In (Tol + BBM + Sopir) murni tanpa uang muka (0% DP), bayar langsung di lokasi tujuan!</p>
            <a href="https://wa.me/<?= $csAiPhone ?>?text=<?= urlencode('Halo Admin Dropoff.id, saya sedang membaca artikel ' . $title . '. Mau tanya rute dan booking drop-off') ?>" class="btn-cta-wa" target="_blank" rel="noopener">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; flex-shrink: 0;"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                <span>Hubungi Customer Service</span>
            </a>
        </div>
    </article>
</main>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> Dropoff.id Indonesia &bull; Panduan Tarif &amp; Edukasi Perjalanan Drop-Off</p>
</footer>

<?php require __DIR__ . '/api/yoast_widget.php'; ?>

</body>
</html>
