<?php
/**
 * TRAVEEGO DYNAMIC BLOG POST RENDERER
 * Menampilkan Artikel Blog SEO hasil karya Mbak Maya (Post Crew)
 * Mendukung Auto-Scheduled sesuai Kalender Konten Mbak Dinda.
 */
require_once __DIR__ . '/config.php';

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
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='utf-8'><title>404 Artikel Tidak Ditemukan | Traveego Blog</title><meta name='viewport' content='width=device-width, initial-scale=1'><style>body{font-family:sans-serif; text-align:center; padding:50px; background:#F8FAFC; color:#0F172A;} a{color:#0B4FD8; text-decoration:none; font-weight:700;}</style></head><body><h1>404</h1><p>Artikel blog yang Anda cari belum tersedia.</p><a href='/'>← Kembali ke Beranda Traveego</a></body></html>";
    exit;
}

$postData = json_decode(file_get_contents($postFile), true) ?: [];

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
        <title>Terjadwal: <?= htmlspecialchars($postData['title'] ?? 'Artikel Blog Traveego') ?></title>
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

// Simple Markdown to HTML parser
function markdownToHtml($md) {
    // Escaping
    $lines = explode("\n", $md);
    $html = '';
    $inList = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
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
            $text = $m[1];
            // Replace links [text](url)
            $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $text);
            // Replace bold **text**
            $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
            $html .= "<blockquote>" . $text . "</blockquote>\n";
        } elseif (preg_match('/^[-*] (.*)/', $trimmed, $m)) {
            if (!$inList) { $html .= "<ul>\n"; $inList = true; }
            $text = $m[1];
            $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $text);
            $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
            $html .= "<li>" . $text . "</li>\n";
        } else {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $text = $trimmed;
            $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $text);
            $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
            $html .= "<p>" . $text . "</p>\n";
        }
    }
    if ($inList) { $html .= "</ul>\n"; }
    return $html;
}

$articleHtml = markdownToHtml($rawMarkdown);
$title = htmlspecialchars($postData['title'] ?? 'Panduan Tarif Drop Off Traveego');
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
    <title><?= $title ?> | Traveego Blog</title>
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
        "name": "Traveego Indonesia"
      },
      "publisher": {
        "@type": "Organization",
        "name": "Traveego Indonesia"
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

        /* CTA Bottom */
        .cta-box {
            background: linear-gradient(135deg, #0B4FD8, #073594);
            border-radius: 18px;
            padding: 30px;
            color: #FFFFFF;
            text-align: center;
            margin-top: 40px;
        }
        .cta-box h3 { font-size: 20px; font-weight: 800; margin-bottom: 8px; }
        .cta-box p { font-size: 14px; color: #DBEAFE; margin-bottom: 20px; }
        .btn-cta-wa {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #16A34A;
            color: #FFFFFF;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
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
        <span>TRAVEEGO BLOG</span>
    </a>
    <a href="https://api.whatsapp.com/send?phone=628190000000&text=Halo%20Traveego,%20saya%20membaca%20artikel%20<?= urlencode($title) ?>" class="btn-topbar-wa" target="_blank" rel="noopener">
        <span>💬</span>
        <span>Chat CS Mbak Hasna</span>
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
        <span>🛡️ Panduan Resmi Traveego</span>
    </div>

    <?php if (!empty($featuredImage)): ?>
    <div style="margin-bottom: 30px; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06);">
        <img src="<?= htmlspecialchars($featuredImage) ?>" alt="<?= htmlspecialchars($title) ?>" style="width: 100%; height: auto; max-height: 440px; object-fit: cover; display: block;" loading="lazy">
    </div>
    <?php endif; ?>

    <article class="article-body">
        <?= $articleHtml ?>

        <div class="cta-box">
            <h3>Mau Bepergian Tanpa Capek Menyetir?</h3>
            <p>Nikmati layanan sewa mobil Drop-Off privat Traveego. Tarif All-In (Tol + BBM + Sopir) murni tanpa uang muka (0% DP), bayar di tujuan!</p>
            <a href="https://api.whatsapp.com/send?phone=628190000000&text=Halo%20Traveego,%20saya%20ingin%20tanya%20tarif%20drop%20off" class="btn-cta-wa" target="_blank" rel="noopener">
                <span>💬</span>
                <span>Konsultasi Rute via WhatsApp</span>
            </a>
        </div>
    </article>
</main>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> Traveego Indonesia &bull; Panduan Tarif &amp; Edukasi Perjalanan Drop-Off</p>
</footer>

</body>
</html>
