<?php
require_once __DIR__ . '/../config.php';

$pdo = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? 'stats';

// 1. STATISTIK OPERASIONAL & DAFTAR TRANSAKSI
if ($action === 'stats') {
    try {
        $ordersTotal = $pdo->query("SELECT COUNT(*) AS total FROM orders")->fetch()['total'];
        $auctionCount = $pdo->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'AUCTION'")->fetch()['total'];
        $completedCount = $pdo->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'COMPLETED'")->fetch()['total'];
        $driversCount = $pdo->query("SELECT COUNT(*) AS total FROM drivers")->fetch()['total'];

        $commissions = $pdo->query("
            SELECT SUM(amount) AS total 
            FROM commission_settlements 
            WHERE status = 'VERIFIED_PAID'
        ")->fetch()['total'] ?? 0;

        $latestOrders = $pdo->query("
            SELECT o.*, d.name AS driver_name, cs.status AS settlement_status
            FROM orders o
            LEFT JOIN drivers d ON o.driver_id = d.id
            LEFT JOIN commission_settlements cs ON o.id = cs.order_id
            ORDER BY o.id DESC LIMIT 15
        ")->fetchAll();

        $pendingSettlements = $pdo->query("
            SELECT cs.*, o.tracking_code, o.total_fare, d.name AS driver_name, d.phone AS driver_phone
            FROM commission_settlements cs
            JOIN orders o ON cs.order_id = o.id
            JOIN drivers d ON cs.driver_id = d.id
            WHERE cs.status = 'WAITING_CONFIRMATION'
            ORDER BY cs.created_at DESC
        ")->fetchAll();

        sendJson([
            'success' => true,
            'stats' => [
                'totalOrders' => intval($ordersTotal),
                'activeAuctions' => intval($auctionCount),
                'completedOrders' => intval($completedCount),
                'totalDrivers' => intval($driversCount),
                'totalCommissionPaid' => floatval($commissions)
            ],
            'latestOrders' => $latestOrders,
            'pendingSettlements' => $pendingSettlements,
            'adminBank' => [
                'bank' => ADMIN_BANK_NAME,
                'account' => ADMIN_BANK_ACCOUNT,
                'holder' => ADMIN_BANK_HOLDER
            ]
        ]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 2. VERIFIKASI SETORAN KOMISI 1-KLIK OLEH ADMIN
if ($action === 'verify_settlement') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = intval($input['settlement_id'] ?? 0);
    $status = trim($input['status'] ?? 'VERIFIED_PAID');

    try {
        $stmt = $pdo->prepare("UPDATE commission_settlements SET status = ?, verified_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);

        sendJson([
            'success' => true,
            'message' => $status === 'VERIFIED_PAID' ? 'Setoran komisi berhasil diverifikasi LUNAS!' : 'Setoran ditolak.'
        ]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// 3. RADAR FLEET SEBARAN ARMADA (UNTUK PETA LEAFLET ADMIN)
if ($action === 'fleet') {
    try {
        $stmt = $pdo->query("
            SELECT d.id, d.name, d.phone, d.car_model, d.car_plate, d.car_color, d.rating,
                   d.current_lat, d.current_lng, d.last_location_update, d.is_online,
                   o.tracking_code, o.status AS trip_status
            FROM drivers d
            LEFT JOIN orders o ON d.id = o.driver_id AND o.status IN ('ACCEPTED', 'ON_THE_WAY', 'ARRIVED', 'IN_TRIP')
            WHERE d.is_online = 1
        ");
        $fleet = $stmt->fetchAll();

        sendJson(['success' => true, 'data' => $fleet]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

sendJson(['success' => false, 'message' => 'Action tidak dikenali'], 404);
