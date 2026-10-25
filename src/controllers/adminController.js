const db = require('../config/db');

// Ambil ringkasan dashboard operasional admin
async function getDashboardStats(req, res) {
    try {
        const [ordersTotal] = await db.query('SELECT COUNT(*) AS total FROM orders');
        const [auctionCount] = await db.query("SELECT COUNT(*) AS total FROM orders WHERE status = 'AUCTION'");
        const [completedCount] = await db.query("SELECT COUNT(*) AS total FROM orders WHERE status = 'COMPLETED'");
        const [driversCount] = await db.query('SELECT COUNT(*) AS total FROM drivers');
        
        // Komisi yang sudah terverifikasi lunas
        const [commissions] = await db.query(`
            SELECT SUM(amount) AS total_commission 
            FROM commission_settlements 
            WHERE status = 'VERIFIED_PAID'
        `);

        // Daftar pesanan terbaru
        const latestOrders = await db.query(`
            SELECT o.*, d.name AS driver_name, cs.status AS settlement_status
            FROM orders o
            LEFT JOIN drivers d ON o.driver_id = d.id
            LEFT JOIN commission_settlements cs ON o.id = cs.order_id
            ORDER BY o.id DESC
            LIMIT 15
        `);

        // Daftar setoran komisi yang menunggu verifikasi admin
        const pendingSettlements = await db.query(`
            SELECT cs.*, o.tracking_code, o.total_fare, d.name AS driver_name, d.phone AS driver_phone
            FROM commission_settlements cs
            JOIN orders o ON cs.order_id = o.id
            JOIN drivers d ON cs.driver_id = d.id
            WHERE cs.status = 'WAITING_CONFIRMATION'
            ORDER BY cs.created_at DESC
        `);

        res.json({
            success: true,
            stats: {
                totalOrders: ordersTotal[0].total,
                activeAuctions: auctionCount[0].total,
                completedOrders: completedCount[0].total,
                totalDrivers: driversCount[0].total,
                totalCommissionPaid: commissions[0].total_commission || 0
            },
            latestOrders,
            pendingSettlements
        });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
}

// Verifikasi pelunasan transfer komisi sopir oleh Admin
async function verifySettlement(req, res) {
    try {
        const settlementId = req.params.id;
        const { status } = req.body; // 'VERIFIED_PAID' atau 'REJECTED'

        const valid = ['VERIFIED_PAID', 'REJECTED'];
        if (!valid.includes(status)) {
            return res.status(400).json({ success: false, message: 'Status tidak valid' });
        }

        const sql = `
            UPDATE commission_settlements 
            SET status = ?, verified_at = NOW() 
            WHERE id = ?
        `;
        await db.query(sql, [status, settlementId]);

        res.json({
            success: true,
            message: status === 'VERIFIED_PAID' ? 'Setoran komisi berhasil diverifikasi LUNAS!' : 'Setoran ditolak.'
        });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
}

module.exports = {
    getDashboardStats,
    verifySettlement
};
