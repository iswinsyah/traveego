<?php
/**
 * API BACKEND UNTUK CREW AI DASHBOARD (/crew-ai)
 */
require_once __DIR__ . '/../config.php';

requireCrewAuth();

$action = $_GET['action'] ?? '';
$baseDir = dirname(__DIR__);
$dataDir = $baseDir . '/data';
$matrixFile = $dataDir . '/cost_matrix.json';
$pagesDir = $dataDir . '/generated_pages';
$postsDir = $dataDir . '/generated_posts';
$aiConfigFile = $dataDir . '/ai_server_config.json';

// Helper: Cek jam operasional server 06:00 - 23:00 WIB
function getWorkingHoursStatus() {
    // Timezone Asia/Jakarta (WIB)
    date_default_timezone_set('Asia/Jakarta');
    $hour = (int)date('H');
    $timeStr = date('H:i:s') . ' WIB';
    $isWorking = ($hour >= 6 && $hour < 23);
    return [
        'current_time' => $timeStr,
        'current_hour' => $hour,
        'is_working' => $isWorking,
        'hours_label' => '06:00 s.d. 23:00 WIB',
        'status_text' => $isWorking ? '🟢 AKTIF (Dalam Jam Operasional Server)' : '🟡 ISTIRAHAT (Di Luar Jam Kerja Server)'
    ];
}

// Helper: Cek koneksi port Ollama via Tailscale / Lokal
function checkOllamaPort($configFile) {
    $cfg = [
        'ollama_host' => 'http://100.85.151.80:11434',
        'api_endpoint' => 'http://100.85.151.80:11434/v1',
        'model' => 'qwen2.5-coder:7b',
        'tailscale_ip' => '100.85.151.80'
    ];

    if (file_exists($configFile)) {
        $loaded = json_decode(file_get_contents($configFile), true);
        if (is_array($loaded)) {
            $cfg = array_merge($cfg, $loaded);
        }
    }

    $parsed = parse_url($cfg['ollama_host']);
    $host = $parsed['host'] ?? '100.85.151.80';
    $port = $parsed['port'] ?? 11434;

    // Timeout kilat 0.3s
    $fp = @fsockopen($host, $port, $errno, $errstr, 0.3);
    $models = [];

    if ($fp) {
        fclose($fp);
        // Coba ambil daftar model terinstall via /api/tags
        $tagsUrl = rtrim($cfg['ollama_host'], '/') . '/api/tags';
        $ctx = stream_context_create(['http' => ['timeout' => 1.5]]);
        $tagsJson = @file_get_contents($tagsUrl, false, $ctx);
        if ($tagsJson) {
            $parsedTags = json_decode($tagsJson, true);
            if (!empty($parsedTags['models'])) {
                foreach ($parsedTags['models'] as $m) {
                    $models[] = $m['name'];
                }
            }
        }

        return [
            'connected' => true,
            'message' => 'Otak AI PC Server TERHUBUNG di ' . $cfg['ollama_host'] . ' via Tailscale',
            'endpoint' => $cfg['ollama_host'],
            'model' => $cfg['model'],
            'available_models' => $models,
            'config' => $cfg
        ];
    }

    return [
        'connected' => false,
        'message' => 'Server AI ' . $cfg['ollama_host'] . ' belum terdeteksi. Pastikan Tailscale aktif dan Ollama berjalan di PC Server.',
        'endpoint' => $cfg['ollama_host'],
        'model' => $cfg['model'],
        'available_models' => [],
        'config' => $cfg
    ];
}

switch ($action) {
    case 'status':
        $wh = getWorkingHoursStatus();
        $ollama = checkOllamaPort($aiConfigFile);

        $pageCount = is_dir($pagesDir) ? count(glob($pagesDir . '/*.json')) : 0;
        $postCount = is_dir($postsDir) ? count(glob($postsDir . '/*.json')) : 0;

        $matrix = file_exists($matrixFile) ? json_decode(file_get_contents($matrixFile), true) : null;
        $routeCount = $matrix && isset($matrix['routes']) ? count($matrix['routes']) : 0;

        sendJson([
            'success' => true,
            'working_hours' => $wh,
            'ollama' => $ollama,
            'stats' => [
                'routes_count' => $routeCount,
                'pages_count' => $pageCount,
                'posts_count' => $postCount,
                'last_updated' => $matrix['last_updated'] ?? '-'
            ]
        ]);
        break;

    case 'save_ai_config':
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;

        $host = trim($payload['ollama_host'] ?? 'http://100.85.151.80:11434');
        $model = trim($payload['model'] ?? 'qwen2.5-coder:7b');

        $cfgData = [
            'ollama_host' => rtrim($host, '/'),
            'api_endpoint' => rtrim($host, '/') . '/v1',
            'model' => $model,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        file_put_contents($aiConfigFile, json_encode($cfgData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $testResult = checkOllamaPort($aiConfigFile);

        sendJson([
            'success' => true,
            'message' => 'Konfigurasi Server AI berhasil disimpan.',
            'ollama' => $testResult
        ]);
        break;

    case 'get_matrix':
        if (!file_exists($matrixFile)) {
            sendJson(['success' => false, 'message' => 'File cost_matrix.json belum ada. Silakan jalankan kalkulasi awal.'], 404);
        }
        $data = json_decode(file_get_contents($matrixFile), true);
        sendJson(['success' => true, 'data' => $data]);
        break;

    case 'get_pages':
        $pages = [];
        $calFile = $dataDir . '/content_calendar.json';
        $calMap = [];
        if (file_exists($calFile)) {
            $calData = json_decode(file_get_contents($calFile), true) ?: [];
            foreach (($calData['schedule'] ?? []) as $s) {
                if (($s['type'] ?? '') === 'page') {
                    $calMap[$s['slug']] = $s;
                }
            }
        }

        date_default_timezone_set('Asia/Jakarta');
        $now = date('Y-m-d H:i:s');

        if (is_dir($pagesDir)) {
            $files = glob($pagesDir . '/*.json');
            foreach ($files as $f) {
                $content = json_decode(file_get_contents($f), true);
                if ($content) {
                    $slug = $content['slug'] ?? basename($f, '.json');
                    $sched = $calMap[$slug] ?? null;
                    $publishAt = $sched['publish_at'] ?? ($content['generated_at'] ?? $now);
                    $isLive = ($now >= $publishAt);

                    $content['publish_at'] = $publishAt;
                    $content['is_live'] = $isLive;
                    $content['status_label'] = $isLive ? '🟢 LIVE (Tayang)' : '⏳ TERJADWAL (' . date('d M H:i', strtotime($publishAt)) . ' WIB)';
                    $content['public_url'] = '/' . $slug;

                    $pages[] = [
                        'file' => basename($f),
                        'data' => $content
                    ];
                }
            }
        }
        sendJson(['success' => true, 'pages' => $pages]);
        break;

    case 'get_posts':
        $posts = [];
        $calFile = $dataDir . '/content_calendar.json';
        $calMap = [];
        if (file_exists($calFile)) {
            $calData = json_decode(file_get_contents($calFile), true) ?: [];
            foreach (($calData['schedule'] ?? []) as $s) {
                if (($s['type'] ?? '') === 'post') {
                    $calMap[$s['slug']] = $s;
                }
            }
        }

        date_default_timezone_set('Asia/Jakarta');
        $now = date('Y-m-d H:i:s');

        if (is_dir($postsDir)) {
            $files = glob($postsDir . '/*.json');
            foreach ($files as $f) {
                $content = json_decode(file_get_contents($f), true);
                if ($content) {
                    $slug = $content['slug'] ?? basename($f, '.json');
                    $sched = $calMap[$slug] ?? null;
                    $publishAt = $sched['publish_at'] ?? ($content['published_at'] ?? $now);
                    $isLive = ($now >= $publishAt);

                    $content['publish_at'] = $publishAt;
                    $content['is_live'] = $isLive;
                    $content['status_label'] = $isLive ? '🟢 LIVE (Tayang)' : '⏳ TERJADWAL (' . date('d M H:i', strtotime($publishAt)) . ' WIB)';
                    $content['public_url'] = '/blog/' . $slug;

                    $posts[] = [
                        'file' => basename($f),
                        'data' => $content
                    ];
                }
            }
        }
        sendJson(['success' => true, 'posts' => $posts]);
        break;

    case 'run_pipeline':
        // Jalankan orchestrator python
        $pyCmd = 'python ' . escapeshellarg($baseDir . '/ai_crew/orchestrator.py') . ' --run-all --force 2>&1';
        $output = [];
        $returnCode = 0;
        exec($pyCmd, $output, $returnCode);

        $logText = implode("\n", $output);
        sendJson([
            'success' => ($returnCode === 0),
            'log' => $logText,
            'return_code' => $returnCode
        ]);
        break;

    default:
        sendJson(['success' => false, 'message' => 'Aksi tidak valid.'], 400);
}
