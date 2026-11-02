<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: config\n";
require_once __DIR__ . '/../config.php';

echo "Step 2: ledger_helper\n";
require_once __DIR__ . '/ledger_helper.php';

echo "Step 3: driver\n";
try {
    $pdo = getDB(false);
    echo "DB connected: " . ($pdo ? "YES" : "NO") . "\n";
} catch (Throwable $e) {
    echo "DB error: " . $e->getMessage() . "\n";
}

echo "Step 4: Done OK!\n";
