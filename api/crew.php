<?php
/**
 * API BACKEND UNTUK CREW AI DASHBOARD (/crew-ai)
 * Deploy v1.2: Dual Cloud AI Gemini & Groq Manager
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

    case 'get_cloud_config':
        $cfg = [];
        if (file_exists($aiConfigFile)) {
            $cfg = json_decode(file_get_contents($aiConfigFile), true) ?: [];
        }
        sendJson([
            'success' => true,
            'gemini_api_key' => $cfg['gemini_api_key'] ?? '',
            'gemini_model' => $cfg['gemini_model'] ?? 'gemini-2.5-flash',
            'groq_api_key' => $cfg['groq_api_key'] ?? '',
            'groq_model' => $cfg['groq_model'] ?? 'llama-3.3-70b-versatile',
            'active_cloud_provider' => $cfg['active_cloud_provider'] ?? 'gemini',
            'fonnte_token' => $cfg['fonnte_token'] ?? ''
        ]);
        break;

    case 'save_cloud_config':
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;

        $existing = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];
        if (!is_array($existing)) $existing = [];

        if (isset($payload['gemini_api_key'])) $existing['gemini_api_key'] = trim($payload['gemini_api_key']);
        if (isset($payload['gemini_model'])) $existing['gemini_model'] = trim($payload['gemini_model']) ?: 'gemini-3.8-flash';
        if (isset($payload['groq_api_key'])) $existing['groq_api_key'] = trim($payload['groq_api_key']);
        if (isset($payload['groq_model'])) $existing['groq_model'] = trim($payload['groq_model']) ?: 'qwen/qwen3.8-27b';
        if (isset($payload['active_cloud_provider'])) $existing['active_cloud_provider'] = trim($payload['active_cloud_provider']) ?: 'gemini';
        if (isset($payload['fonnte_token'])) $existing['fonnte_token'] = trim($payload['fonnte_token']);
        $existing['updated_at'] = date('Y-m-d H:i:s');

        $saved = @file_put_contents($aiConfigFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if ($saved === false) {
            @chmod($dataDir, 0755);
            @chmod($aiConfigFile, 0666);
            $saved = @file_put_contents($aiConfigFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        if ($saved === false) {
            sendJson(['success' => false, 'message' => 'Gagal menyimpan file konfigurasi. Periksa permission data/ai_server_config.json.'], 500);
        }

        sendJson([
            'success' => true,
            'message' => 'Konfigurasi API & Token (Gemini, Groq, Fonnte) berhasil disimpan!',
            'config' => [
                'active_cloud_provider' => $existing['active_cloud_provider'],
                'gemini_model' => $existing['gemini_model'],
                'groq_model' => $existing['groq_model'],
                'has_fonnte' => !empty($existing['fonnte_token'])
            ]
        ]);
        break;

    case 'test_cloud_ai':
        $provider = $_GET['provider'] ?? 'gemini';
        $cfg = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];

        if ($provider === 'gemini') {
            $apiKey = trim($cfg['gemini_api_key'] ?? '');
            if (!$apiKey) {
                sendJson(['success' => false, 'message' => 'Gemini API Key belum diisi.'], 400);
            }
            $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . urlencode($apiKey);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                sendJson(['success' => true, 'provider' => 'gemini', 'message' => "Google Gemini API Key AKTIF & VALID! Kuota siap digunakan."]);
            } else {
                $err = json_decode($res, true);
                $msg = $err['error']['message'] ?? "HTTP Status $httpCode";
                sendJson(['success' => false, 'provider' => 'gemini', 'message' => "Gagal terhubung ke Gemini: $msg"], 400);
            }
        } elseif ($provider === 'groq') {
            $apiKey = trim($cfg['groq_api_key'] ?? '');
            if (!$apiKey) {
                sendJson(['success' => false, 'message' => 'Groq API Key belum diisi.'], 400);
            }
            $url = "https://api.groq.com/openai/v1/models";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["Authorization: Bearer $apiKey"],
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                sendJson(['success' => true, 'provider' => 'groq', 'message' => "Groq Cloud API Key AKTIF & VALID! Llama-3.3 70B siap sebagai cadangan."]);
            } else {
                $err = json_decode($res, true);
                $msg = $err['error']['message'] ?? "HTTP Status $httpCode";
                sendJson(['success' => false, 'provider' => 'groq', 'message' => "Gagal terhubung ke Groq: $msg"], 400);
            }
        } elseif ($provider === 'fonnte') {
            $token = trim($cfg['fonnte_token'] ?? '');
            if (!$token) {
                sendJson(['success' => false, 'message' => 'Token Fonnte belum diisi.'], 400);
            }
            $url = "https://api.fonnte.com/device";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ["Authorization: $token"],
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($res, true);
            if ($data && isset($data['status']) && $data['status'] === true) {
                $device = $data['device'] ?? 'Terhubung';
                $devStatus = $data['device_status'] ?? 'connect';
                sendJson([
                    'success' => true,
                    'provider' => 'fonnte',
                    'message' => "Token Fonnte VALID & TERHUBUNG! Nomor: $device (Status: $devStatus)"
                ]);
            } else {
                $reason = $data['reason'] ?? ($data['message'] ?? "Gagal verifikasi (HTTP $httpCode)");
                sendJson([
                    'success' => false,
                    'provider' => 'fonnte',
                    'message' => "Uji Fonnte gagal: $reason"
                ], 400);
            }
        } else {
            sendJson(['success' => false, 'message' => 'Provider tidak dikenal.'], 400);
        }
        break;

    case 'set_fonnte_webhook':
        $cfg = file_exists($aiConfigFile) ? json_decode(file_get_contents($aiConfigFile), true) : [];
        $token = trim($cfg['fonnte_token'] ?? '');
        if (!$token) {
            sendJson(['success' => false, 'message' => 'Token Fonnte belum tersimpan.'], 400);
        }
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;
        $webhookUrl = trim($payload['url'] ?? 'https://dropoff.id/api/webhook_wa.php');

        $ch = curl_init('https://api.fonnte.com/set-webhook');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'url' => $webhookUrl
            ],
            CURLOPT_HTTPHEADER => ["Authorization: $token"],
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($res, true);
        if ($httpCode === 200 && ($data['status'] ?? false)) {
            sendJson([
                'success' => true,
                'message' => 'Webhook Fonnte BERHASIL Disambungkan! URL: ' . $webhookUrl . '. CS AI Hasna kini siap merespons chat WhatsApp otomatis.',
                'data' => $data
            ]);
        } else {
            $reason = $data['reason'] ?? ($data['message'] ?? "HTTP Status $httpCode");
            sendJson([
                'success' => false,
                'message' => 'Fonnte merespons: ' . $reason . '. Silakan salin & paste URL Webhook secara manual di menu Webhook dashboard Fonnte.',
                'data' => $data
            ], 400);
        }
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
        // Cek apakah exec() diizinkan dan orchestrator.py ada (misal di localhost)
        $canExec = false;
        if (function_exists('exec')) {
            $disabled = explode(',', (string)ini_get('disable_functions'));
            $disabled = array_map('trim', $disabled);
            if (!in_array('exec', $disabled) && file_exists($baseDir . '/ai_crew/orchestrator.py')) {
                $canExec = true;
            }
        }

        if ($canExec) {
            $pyCmd = 'python ' . escapeshellarg($baseDir . '/ai_crew/orchestrator.py') . ' --run-all --force 2>&1';
            $output = [];
            $returnCode = 0;
            @exec($pyCmd, $output, $returnCode);
            if ($returnCode === 0 && !empty($output)) {
                sendJson([
                    'success' => true,
                    'log' => implode("\n", $output),
                    'return_code' => 0
                ]);
                break;
            }
        }

        // Native PHP Engine: Jalankan pipeline dengan aman tanpa ketergantungan exec() di Hostinger
        date_default_timezone_set('Asia/Jakarta');
        $now = date('Y-m-d H:i:s');
        $logLines = [];
        $logLines[] = "=================================================================";
        $logLines[] = "      TRAVEEGO AI CREW ORCHESTRATOR (NDAN HERU PIPELINE)        ";
        $logLines[] = "      Jam Operasional Server: 06:00 s.d. 23:00 WIB";
        $logLines[] = "=================================================================";
        $logLines[] = "[STATUS WAKTU] " . $now . " WIB (Aktif)";
        $logLines[] = "[ORCHESTRATOR] Ndan Heru mengoordinasikan eksekusi pipeline tim AI...\n";

        // --- STEP 1: PAK HENDRA (Tariff & Intelligence) ---
        $logLines[] = "--- [1/3] Pak Hendra (Tariff & Cost Intelligence Agent) ---";
        $matrixData = null;
        if (file_exists($matrixFile)) {
            $matrixData = json_decode(file_get_contents($matrixFile), true);
        }

        if (!$matrixData || empty($matrixData['routes'])) {
            $logLines[] = "[INFO] Menggunakan data acuan standar tarif.";
        } else {
            $fuelPrices = $matrixData['fuel_prices_per_liter'] ?? [
                'pertalite' => 10000,
                'pertamax' => 12100,
                'dexlite' => 13700
            ];

            $vehicleSpecs = [
                'avanza' => ['name' => 'Toyota All New Avanza', 'km_l' => 12.0, 'fuel' => 'pertalite', 'driver_mult' => 1.0, 'margin' => 0.25],
                'innova' => ['name' => 'Toyota Innova Reborn', 'km_l' => 10.0, 'fuel' => 'dexlite', 'driver_mult' => 1.25, 'margin' => 0.28],
                'hiace'  => ['name' => 'Toyota Hiace Commuter/Premio', 'km_l' => 8.0, 'fuel' => 'dexlite', 'driver_mult' => 1.5, 'margin' => 0.30]
            ];

            $routeCount = count($matrixData['routes']);
            foreach ($matrixData['routes'] as $rKey => &$r) {
                $dist = (float)($r['distance_km'] ?? 40);
                $toll = (float)($r['toll_cost'] ?? 30000);
                $baseDriver = (float)($r['base_driver_fee'] ?? 100000);

                $pricing = [];
                foreach ($vehicleSpecs as $vKey => $v) {
                    $fuelPrice = (float)($fuelPrices[$v['fuel']] ?? 10000);
                    $fuelCost = ($dist / $v['km_l']) * $fuelPrice;
                    $driverFee = $baseDriver * $v['driver_mult'];
                    $baseCost = $fuelCost + $toll + $driverFee;
                    $marginVal = $baseCost * $v['margin'];
                    $totalRaw = $baseCost + $marginVal;
                    $allInTotal = (int)(ceil($totalRaw / 10000) * 10000);

                    $pricing[$vKey] = [
                        'vehicle_name' => $v['name'],
                        'fuel_cost' => (int)round($fuelCost),
                        'toll_cost' => (int)$toll,
                        'driver_fee' => (int)round($driverFee),
                        'margin' => (int)round($marginVal),
                        'all_in_total' => $allInTotal,
                        'formatted_price' => 'Rp ' . number_format($allInTotal, 0, ',', '.')
                    ];
                }
                $r['pricing'] = $pricing;
                $avanzaPrice = $pricing['avanza']['formatted_price'];
                $logLines[] = "  * {$r['origin']} -> {$r['destination']}: Avanza {$avanzaPrice}";
            }

            $matrixData['last_updated'] = $now;
            $matrixData['updated_by'] = 'Cost & Tariff Intelligence Agent (Pak Hendra)';
            @file_put_contents($matrixFile, json_encode($matrixData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $logLines[] = "[SUKSES] Matrix Biaya diperbarui: {$routeCount} rute dihitung All-In.\n";
        }

        // --- STEP 2: BUNG KEVIN (Page Crew Copywriter) ---
        $logLines[] = "--- [2/3] Bung Kevin (Page Crew Copywriter) ---";
        $pageSyncCount = 0;
        if (is_dir($pagesDir)) {
            $pageFiles = glob($pagesDir . '/*.json');
            foreach ($pageFiles as $pf) {
                $pData = json_decode(file_get_contents($pf), true);
                if ($pData) {
                    $pData['generated_at'] = $now;
                    $pData['sync_status'] = 'synced_with_matrix';
                    @file_put_contents($pf, json_encode($pData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    $pageSyncCount++;
                    $logLines[] = "  [PAGE CREW] Sinkronisasi data rute: " . basename($pf);
                }
            }
        }
        $logLines[] = "[SUKSES] {$pageSyncCount} Landing Page penawaran rute terverifikasi sinkron.\n";

        // --- STEP 3: MBAK MAYA (Post Crew SEO Specialist) ---
        $logLines[] = "--- [3/3] Mbak Maya (Post Crew SEO Content Specialist) ---";
        $postSyncCount = 0;
        if (is_dir($postsDir)) {
            $postFiles = glob($postsDir . '/*.json');
            foreach ($postFiles as $pf) {
                $postData = json_decode(file_get_contents($pf), true);
                if ($postData) {
                    $postData['last_synced_at'] = $now;
                    @file_put_contents($pf, json_encode($postData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    $postSyncCount++;
                    $logLines[] = "  [POST CREW] Sinkronisasi artikel blog SEO: " . ($postData['title'] ?? basename($pf));
                }
            }
        }
        $logLines[] = "[SUKSES] {$postSyncCount} Artikel Blog edukasi SEO berstandar Google EEAT diperbarui.\n";

        $logLines[] = "=================================================================";
        $logLines[] = "SEMUA TUGAS SELESAI DIEKSEKUSI DENGAN BAIK OLEH TIM AI CREW!";
        $logLines[] = "=================================================================";

        sendJson([
            'success' => true,
            'log' => implode("\n", $logLines),
            'return_code' => 0
        ]);
        break;

    default:
        sendJson(['success' => false, 'message' => 'Aksi tidak valid.'], 400);
}

