<?php
/**
 * AUTONOMOUS HOSTINGER DATABASE INITIALIZER & AUTO-MIGRATOR
 * Memastikan semua tabel di u829486010_dbasedropoff langsung terbuat otomatis
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

try {
    $pdo = getDB(false);
    if (!$pdo) {
        // Coba koneksi langsung dengan kredensial untuk mendeteksi pesan error presisi
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    $sqlFile = __DIR__ . '/../scripts/u829486010_dbasedropoff.sql';
    if (!file_exists($sqlFile)) {
        echo json_encode(['success' => false, 'message' => 'File schema SQL tidak ditemukan']);
        exit;
    }

    $sqlContent = file_get_contents($sqlFile);
    // Jalankan seluruh DDL & Seed queries
    $pdo->exec($sqlContent);

    // Ambil daftar tabel yang ada
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'message' => '🎉 Koneksi MySQL Berhasil & Seluruh Tabel Database Telah Terbuat Otomatis!',
        'database' => DB_NAME,
        'user' => DB_USER,
        'total_tables' => count($tables),
        'tables' => $tables
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal koneksi atau inisialisasi database: ' . $e->getMessage(),
        'database' => DB_NAME,
        'user' => DB_USER
    ], JSON_PRETTY_PRINT);
}
