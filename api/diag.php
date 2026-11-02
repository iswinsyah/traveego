<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_GET['action'] = 'balance';
$_GET['driver_id'] = 1;

require __DIR__ . '/driver.php';
