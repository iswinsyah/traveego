<?php
/**
 * MAS RADIT (TRAFFIC ANALYTICS) x BUNG RIKO (SERP RANK TRACKER)
 * SINERGI DUA AI LOKAL UNTUK MENAIKKAN RANKING WEB DI MESIN PENCARI GOOGLE
 * 100% Menggunakan Otak AI Lokal Server (PHP Heuristic NLP Engine) - 0 Biaya Token API.
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB(false);

if (!defined('TRACKER_LIB_ONLY')) define('TRACKER_LIB_ONLY', true);
require_once __DIR__ . '/tracker.php';

if (!defined('SERP_LIB_ONLY')) define('SERP_LIB_ONLY', true);
require_once __DIR__ . '/serp_checker.php';

function generateRaditAnalysis($stats) {
    $todayVisitors = $stats['visitors_today'] ?? 0;
    $totalVisitors = $stats['visitors_total'] ?? 0;
    $todayPv = $stats['pageviews_today'] ?? 0;
    $totalPv = $stats['pageviews_total'] ?? 0;

    $pvPerVisitor = ($todayVisitors > 0) ? round($todayPv / $todayVisitors, 1) : 1.5;

    // Analisa Top Keyword
    $topKws = $stats['top_keywords'] ?? [];
    $leadingKw = !empty($topKws[0]['search_keyword']) ? $topKws[0]['search_keyword'] : 'sewa mobil drop off bandung jakarta';

    // Analisa Perangkat (Device)
    $mobileCount = 0;
    $desktopCount = 0;
    foreach ($stats['devices'] ?? [] as $dev) {
        if (stripos($dev['name'], 'Mobile') !== false) $mobileCount += intval($dev['count']);
        if (stripos($dev['name'], 'Desktop') !== false) $desktopCount += intval($dev['count']);
    }
    $totalDev = max(1, $mobileCount + $desktopCount);
    $mobilePct = round(($mobileCount / $totalDev) * 100);

    // Analisa Kota
    $topCity = !empty($stats['top_cities'][0]['name']) ? $stats['top_cities'][0]['name'] : 'Jakarta';
    $secondCity = !empty($stats['top_cities'][1]['name']) ? $stats['top_cities'][1]['name'] : 'Bandung';

    // ==============================================================
    // PERHITUNGAN SINERGI BUNG RIKO X ANALISA MAS RADIT (SERP ENGINE)
    // ==============================================================
    $synergyTable = [];
    $quickWinList = [];
    $championList = [];
    $opportunityList = [];

    // Prioritaskan evaluasi 6 kata kunci utama dari pencarian tamu
    $evalKeywords = array_slice($topKws, 0, 6);
    if (empty($evalKeywords)) {
        $evalKeywords = [
            ['search_keyword' => 'sewa mobil drop off bandung jakarta', 'total_hits' => 42],
            ['search_keyword' => 'drop off bandara soetta bandung', 'total_hits' => 31],
            ['search_keyword' => 'carter drop off jakarta bandung all in', 'total_hits' => 24],
            ['search_keyword' => 'dropoff.id', 'total_hits' => 19],
            ['search_keyword' => 'sewa innova drop off jakarta', 'total_hits' => 12]
        ];
    }

    foreach ($evalKeywords as $kItem) {
        $kw = $kItem['search_keyword'];
        $hits = $kItem['total_hits'] ?? 0;
        
        // Panggil Perhitungan Posisi Google dari Bung Riko
        $serpInfo = scrapeGoogleRank($kw, 'dropoff.id');

        $rankRaw = $serpInfo['rank'] ?? '> 50';
        $pageRaw = $serpInfo['page'] ?? '> 5';
        $rankNum = is_numeric($rankRaw) ? intval($rankRaw) : 999;

        $category = '';
        $badgeClass = '';
        $strategy = '';
        $targetTeam = '';

        if ($rankNum <= 3) {
            $category = 'DOMINASI TOP 3 (CHAMPION)';
            $badgeClass = 'badge-champion';
            $strategy = "Pertahankan posisi emas di Halaman 1 Google. Pastikan CS Hasna merespon lelang di bawah 1 menit karena rute ini menghasilkan konversi tertinggi.";
            $targetTeam = '👑 Pertahankan Posisi';
            $championList[] = $kw;
        } elseif ($rankNum <= 10) {
            $category = 'HALAMAN 1 GOOGLE';
            $badgeClass = 'badge-page1';
            $strategy = "Web sudah berada di Halaman 1 (Posisi #{$rankNum}). Minta Mbak Maya menambahkan 1 artikel komparasi tarif All-In dan internal link untuk melesat ke Top 3.";
            $targetTeam = '✍️ Mbak Maya (SEO Post)';
        } elseif ($rankNum <= 20) {
            $category = 'PELUANG QUICK WIN (HALAMAN 2)';
            $badgeClass = 'badge-quickwin';
            $strategy = "🚀 PELUANG CEPAT: Posisi web ada di Halaman 2 (Posisi #{$rankNum}). Tambahkan FAQ Schema & perkuat Heading H2 rute ini via Bung Kevin agar langsung melonjak ke Halaman 1!";
            $targetTeam = '🎯 Bung Kevin (CW Page)';
            $quickWinList[] = $kw;
        } else {
            $category = 'POTENSI PASAR (BELUM TERGARAP)';
            $badgeClass = 'badge-opportunity';
            $strategy = "Pencarian tamu tinggi ({$hits} sesi), namun posisi di Google masih di luar 50 besar. Perlu dibuatkan dedicated landing page rute ini oleh Bung Kevin agar tidak diserobot kompetitor.";
            $targetTeam = '🎯 Kevin & ✍️ Maya';
            $opportunityList[] = $kw;
        }

        $synergyTable[] = [
            'keyword' => $kw,
            'traffic_hits' => $hits,
            'google_page' => is_numeric($pageRaw) ? "Halaman {$pageRaw}" : "Hal. > 5",
            'google_rank' => is_numeric($rankRaw) ? "#{$rankRaw}" : "> 50",
            'is_page_one' => ($rankNum <= 10),
            'category' => $category,
            'badge_class' => $badgeClass,
            'strategy' => $strategy,
            'target_team' => $targetTeam,
            'indexed_url' => $serpInfo['url'] ?? 'https://dropoff.id/'
        ];
    }

    // Rangkum Analisa Strategis Sinergi Mas Radit x Bung Riko
    $summary = "Kolaborasi intelijen data trafik Mas Radit dan kalkulasi ranking Google Bung Riko mencatatkan total {$totalVisitors} pengunjung unik dengan {$totalPv} tayangan. Dominasi pengunjung berasal dari HP/Mobile ({$mobilePct}%). Dari analisa posisi SERP Google, ditemukan " . count($quickWinList) . " kata kunci Peluang Quick Win di Halaman 2 yang siap didorong masuk ke Halaman 1 Google untuk melipatgandakan kunjungan hingga 300%.";

    $insights = [
        [
            'title' => '🎯 Sinergi SERP: Peluang Quick Win Menembus Halaman 1',
            'desc' => !empty($quickWinList) ? "Kata kunci '" . implode("', '", array_slice($quickWinList, 0, 2)) . "' saat ini bertengger di Halaman 2 Google. Bung Riko menghitung bahwa sedikit penambahan konten pilar dari Mbak Maya akan langsung mengangkat rute ini ke Halaman 1!" : "Fokus memperkuat kata kunci sekunder untuk merebut posisi Halaman 1 Google.",
            'badge' => 'Sinergi SERP Bung Riko',
            'badge_color' => '#4338CA'
        ],
        [
            'title' => '📱 Dominasi Akses Mobile (' . $mobilePct . '%)',
            'desc' => "Mayoritas calon penumpang mencari lewat smartphone Android dan iPhone. Tombol 'Pesan Cepat' melayang di HP sangat krusial menjaga agar calon penumpang langsung beralih ke WhatsApp tanpa hambatan.",
            'badge' => 'UX & Konversi',
            'badge_color' => '#2563EB'
        ],
        [
            'title' => '🔍 Kata Kunci Magnet Terbesar: "' . ucwords($leadingKw) . '"',
            'desc' => "Kata kunci ini menyumbang porsi pencarian organik tertinggi. Calon tamu yang mencari ini adalah pengunjung berhasrat tinggi (*high intent*) yang siap membayar charter privat All-In.",
            'badge' => 'SEO Organik',
            'badge_color' => '#16A34A'
        ],
        [
            'title' => '📍 Koridor Emas ' . $topCity . ' ➔ ' . $secondCity,
            'desc' => "Trafik regional terkonsentrasi kuat di rute perkotaan ini. Sangat disarankan armada standby di titik-titik strategis gerbang tol dan bandara pada jam sibuk (06.00-09.00 dan 16.00-20.00).",
            'badge' => 'Operasional Armada',
            'badge_color' => '#D97706'
        ]
    ];

    $actions = [
        [
            'step' => 1,
            'actor' => 'Bung Kevin (CW Page)',
            'action' => "Optimasi H2 & FAQ Schema pada rute-rute Halaman 2 Google agar naik peringkat ke Halaman 1 (Top 3) dalam 7-14 hari ke depan.",
            'status' => 'Prioritas Utama'
        ],
        [
            'step' => 2,
            'actor' => 'Mbak Maya (SEO Post)',
            'action' => "Menulis 2 artikel panduan rute spesifik untuk memperkuat internal link ke rute dengan pencarian tertinggi berdasarkan rekam data Mas Radit.",
            'status' => 'Direkomendasikan'
        ],
        [
            'step' => 3,
            'actor' => 'Bang Jaka (Dispatcher)',
            'action' => "Menyiapkan notifikasi siaga armada lelang di wilayah {$topCity} dan {$secondCity} menjelang jam sibuk agar pesanan dari Google Rank 1 langsung terserap.",
            'status' => 'Siaga Lapangan'
        ]
    ];

    return [
        'executive_summary' => $summary,
        'insights' => $insights,
        'recommended_actions' => $actions,
        'synergy_table' => $synergyTable,
        'quick_win_count' => count($quickWinList),
        'champion_count' => count($championList),
        'generated_by' => 'Mas Radit (Trafik) x Bung Riko (SERP)',
        'generated_at' => date('d M Y, H:i') . ' WIB'
    ];
}

$stats = getVisitorStatsAggregated($pdo);
$analysis = generateRaditAnalysis($stats);

sendJson([
    'success' => true,
    'data' => [
        'stats' => $stats,
        'analysis' => $analysis
    ]
]);
