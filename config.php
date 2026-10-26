<?php
/**
 * KONFIGURASI DATABASE & APLIKASI TRAVEEGO (PHP VERSION)
 * Siap upload langsung ke Hostinger Shared Hosting (public_html)
 */

// 1. KREDENSIAL DATABASE MYSQL
// Saat di XAMPP lokal: host=localhost, user=root, pass=""
// Saat di Hostinger: ganti dengan user & pass database yang Anda buat di hPanel
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'traveego_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// 2. KREDENSIAL LOGIN ADMIN PANEL (/admin)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin@traveego');

// 2b. KREDENSIAL LOGIN CREW AI PANEL (/crew-ai)
define('CREW_USERNAME', 'winsyah');
define('CREW_PASSWORD', 'Khilafet@1924');

function startAdminSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Keamanan cookie session
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }
}

function isAdminLoggedIn() {
    startAdminSession();
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        sendJson(['success' => false, 'message' => 'Akses ditolak: Silakan login terlebih dahulu.'], 401);
    }
}

function isCrewLoggedIn() {
    startAdminSession();
    return !empty($_SESSION['crew_logged_in']) && $_SESSION['crew_logged_in'] === true;
}

function requireCrewAuth() {
    if (!isCrewLoggedIn()) {
        sendJson(['success' => false, 'message' => 'Akses ditolak: Silakan login ke Crew AI.'], 401);
    }
}

// 3. REKENING ADMIN UNTUK SETORAN KOMISI SOPIR (PASCA-BAYAR)
define('ADMIN_BANK_NAME', 'BCA');
define('ADMIN_BANK_ACCOUNT', '1234567890');
define('ADMIN_BANK_HOLDER', 'Traveego Operasional');

// 4. KONEKSI PDO DENGAN ERROR HANDLING
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Gagal koneksi ke database: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    return $pdo;
}

// 4. HELPER JSON RESPONSE
function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data);
    exit;
}
