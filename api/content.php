<?php
/**
 * TRAVEEGO FRONT-END CONTENT API
 * Menyajikan data konfigurasi landing page (Head, Hero, Armada, FAQ, Footer, dll)
 * untuk ditampilkan secara dinamis di index.html.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: public, max-age=30');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$dataFile = __DIR__ . '/../data/site_content.json';

if (file_exists($dataFile)) {
    $content = file_get_contents($dataFile);
    if (!empty($content)) {
        echo $content;
        exit;
    }
}

// Fallback jika file belum terbuat
http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Konten belum diinisialisasi'
]);
