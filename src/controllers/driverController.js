const db = require('../config/db');

// Login sopir via nomor WhatsApp + PIN
async function login(req, res) {
    try {
        const { phone, pin } = req.body;
        
        // Cari sopir berdasarkan nomor HP
        const sql = `SELECT * FROM drivers WHERE phone = ?`;
        const drivers = await db.query(sql, [phone]);

        if (drivers.length === 0) {
            return res.status(404).json({ success: false, message: 'Nomor WhatsApp belum terdaftar sebagai mitra sopir.' });
        }

        const driver = drivers[0];
        if (driver.pin !== pin) {
            return res.status(401).json({ success: false, message: 'PIN yang Anda masukkan salah.' });
        }

        // Ambil pesanan yang sedang aktif dipegang oleh sopir ini
        const activeOrderSql = `
            SELECT o.*, cs.status AS settlement_status, cs.proof_image
            FROM orders o
            LEFT JOIN commission_settlements cs ON o.id = cs.order_id
            WHERE o.driver_id = ? AND o.status NOT IN ('CANCELLED')
            ORDER BY o.id DESC LIMIT 1
        `;
        const [activeOrder] = await db.query(activeOrderSql, [driver.id]);

        res.json({
            success: true,
            message: `Selamat datang kembali, ${driver.name}!`,
            driver: {
                id: driver.id,
                name: driver.name,
                phone: driver.phone,
                car_model: driver.car_model,
                car_plate: driver.car_plate,
                car_color: driver.car_color,
                rating: driver.rating,
                total_trips: driver.total_trips,
                is_trusted: driver.is_trusted
            },
            activeOrder: activeOrder || null
        });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
}

// Ambil riwayat orderan yang diselesaikan sopir
async function getDriverHistory(req, res) {
    try {
        const driverId = req.params.id;
        const sql = `
            SELECT o.*, cs.status AS settlement_status, cs.proof_image
            FROM orders o
            LEFT JOIN commission_settlements cs ON o.id = cs.order_id
            WHERE o.driver_id = ?
            ORDER BY o.id DESC
            LIMIT 20
        `;
        const history = await db.query(sql, [driverId]);
        res.json({ success: true, data: history });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
}

module.exports = {
    login,
    getDriverHistory
};
