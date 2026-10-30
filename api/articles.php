<?php
/**
 * TRAVEEGO API: DAFTAR ARTIKEL PUBLIKASI (BLOG POSTS)
 * Menyajikan artikel terbaru untuk ditampilkan di Homepage dan Blog Archive.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=60');

$postsDir = __DIR__ . '/../data/generated_posts';
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
                    'url' => '/blog/' . $data['slug']
                ];
            }
        }
    }
}

// Urutkan dari yang terbaru
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
