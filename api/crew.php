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

// Helper: Cek koneksi port Ollama 11434
function checkOllamaPort() {
    $fp = @fsockopen('127.0.0.1', 11434, $errno, $errstr, 0.2);
    if ($fp) {
        fclose($fp);
        return [
            'connected' => true,
            'message' => 'Otak AI Lokal TERHUBUNG di http://localhost:11434 (Ollama Aktif)',
            'endpoint' => 'http://localhost:11434'
        ];
    }
    return [
        'connected' => false,
        'message' => 'Otak AI Lokal Belum Terdeteksi di port 11434. Nyalakan Ollama (ollama run qwen2.5:7b) saat Anda siap.',
        'endpoint' => 'http://localhost:11434'
    ];
}

switch ($action) {
    case 'status':
        $wh = getWorkingHoursStatus();
        $ollama = checkOllamaPort();

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

    case 'get_matrix':
        if (!file_exists($matrixFile)) {
            sendJson(['success' => false, 'message' => 'File cost_matrix.json belum ada. Silakan jalankan kalkulasi awal.'], 404);
        }
        $data = json_decode(file_get_contents($matrixFile), true);
        sendJson(['success' => true, 'data' => $data]);
        break;

    case 'get_pages':
        $pages = [];
        if (is_dir($pagesDir)) {
            $files = glob($pagesDir . '/*.json');
            foreach ($files as $f) {
                $content = json_decode(file_get_contents($f), true);
                if ($content) {
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
        if (is_dir($postsDir)) {
            $files = glob($postsDir . '/*.json');
            foreach ($files as $f) {
                $content = json_decode(file_get_contents($f), true);
                if ($content) {
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
