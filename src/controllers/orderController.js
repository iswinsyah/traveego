const db = require('../config/db');

// Helper generator kode tracking TRX-XXXX
function generateTrackingCode() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let code = 'TRX-';
    for (let i = 0; i < 4; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return code;
}

// 1. Ambil daftar order yang sedang dilelang (untuk radar PWA Sopir)
async function getAuctionOrders(req, res) {
    try {
        const sql = `
            SELECT o.*, c1.name AS origin_city, c2.name AS destination_city
            FROM orders o
            LEFT JOIN routes r ON o.route_id = r.id
            LEFT JOIN cities c1 ON r.origin_city_id = c1.id
            LEFT JOIN cities c2 ON r.destination_city_id = c2.id
            WHERE o.status = 'AUCTION'
            ORDER BY o.created_at DESC
        `;
        const orders = await db.query(sql);
        res.json({ success: true, data: orders });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
}

// 2. Buat order baru (dari Web Pelanggan / Form / AI)
async function createOrder(req, res, io) {
    try {
        const {
            customer_name,
            customer_phone,
            pickup_address,
            destination_address,
            pickup_datetime,
            car_type,
            passenger_count,
            notes,
            total_fare,
            route_id
        } = req.body;

        const fare = parseFloat(total_fare) || 700000;
        const commission = fare * 0.10; // 10% hak bagi hasil admin
        const tracking_code = generateTrackingCode();

        const sql = `
            INSERT INTO orders (
                tracking_code, route_id, customer_name, customer_phone,
                pickup_address, destination_address, pickup_datetime,
                car_type, passenger_count, notes, total_fare, admin_commission, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'AUCTION')
        `;

        const result = await db.query(sql, [
            tracking_code,
            route_id || 1,
            customer_name,
            customer_phone,
            pickup_address,
            destination_address,
            pickup_datetime || new Date(),
            car_type || 'Avanza/Xpander',
            passenger_count || 2,
            notes || '',
            fare,
            commission
        ]);

        const newOrder = {
            id: result.insertId,
            tracking_code,
            customer_name,
            pickup_address,
            destination_address,
            car_type: car_type || 'Avanza/Xpander',
            total_fare: fare,
            admin_commission: commission,
            status: 'AUCTION'
        };

        // Broadcast real-time ke semua HP sopir yang membuka PWA!
        if (io) {
            io.emit('new_auction_order', newOrder);
        }

        res.json({
            success: true,
            message: 'Order berhasil diterbitkan ke lelang sopir!',
            order: newOrder
        });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
}

// 3. AMBIL ORDER (ATOMIC LOCK: Siapa Cepat Dia Dapat)
async function acceptOrder(req, res, io) {
    try {
        const orderId = req.params.id;
        const { driver_id } = req.body;

        if (!driver_id) {
            return res.status(400).json({ success: false, message: 'Driver ID wajib disertakan' });
        }

        // Query Atomic Lock: Hanya berhasil jika status masih AUCTION dan driver_id masih NULL
        const updateSql = `
            UPDATE orders 
            SET driver_id = ?, status = 'ACCEPTED', driver_assigned_at = NOW()
            WHERE id = ? AND status = 'AUCTION' AND driver_id IS NULL
        `;

        const result = await db.query(updateSql, [driver_id, orderId]);

        // Cek apakah sopir ini yang menang balapan klik
        if (result.affectedRows === 1) {
            // Berhasil menang! Ambil data lengkap pesanan dan sopir
            const orderSql = `
                SELECT o.*, d.name AS driver_name, d.phone AS driver_phone,
                       d.car_model, d.car_plate, d.car_color
                FROM orders o
                JOIN drivers d ON o.driver_id = d.id
                WHERE o.id = ?
            `;
            const [order] = await db.query(orderSql, [orderId]);

            // Broadcast ke sopir lain bahwa order ini sudah laku diambil
            if (io) {
                io.emit('order_taken', { orderId: parseInt(orderId), winnerDriverId: driver_id });
                // Beritahu layar tracking konsumen secara live
                io.emit(`tracking_update_${order.tracking_code}`, order);
            }

            return res.json({
                success: true,
                message: 'Selamat! Anda berhasil mengambil order ini.',
                data: order
            });
        } else {
            // Kalah cepat, order sudah disambar sopir lain
            return res.status(409).json({
                success: false,
                message: 'Maaf, order ini baru saja diambil oleh rekan sopir yang lain. Pantau radar untuk order berikutnya!'
            });
        }
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
}

// 4. Update status perjalanan (ON_THE_WAY, ARRIVED, IN_TRIP, COMPLETED)
async function updateTripStatus(req, res, io) {
    try {
        const orderId = req.params.id;
        const { status, driver_id } = req.body;

        const validStatuses = ['ON_THE_WAY', 'ARRIVED', 'IN_TRIP', 'COMPLETED', 'CANCELLED'];
        if (!validStatuses.includes(status)) {
            return res.status(400).json({ success: false, message: 'Status tidak valid' });
        }

        let sql = `UPDATE orders SET status = ? WHERE id = ? AND driver_id = ?`;
        if (status === 'COMPLETED') {
            sql = `UPDATE orders SET status = ?, completed_at = NOW() WHERE id = ? AND driver_id = ?`;
        }

        await db.query(sql, [status, orderId, driver_id]);

        // Ambil info order untuk update live ke pelanggan
        const [order] = await db.query('SELECT * FROM orders WHERE id = ?', [orderId]);

        if (io && order) {
            io.emit(`tracking_update_${order.tracking_code}`, order);
            io.emit('order_status_changed', { orderId, status });
        }

        res.json({ success: true, message: `Status perjalanan diubah menjadi ${status}`, order });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
}

// 5. Konfirmasi Setor Komisi Pasca-Bayar oleh Sopir
async function submitCommissionSettlement(req, res) {
    try {
        const orderId = req.params.id;
        const { driver_id, amount, bank_sender, sender_name, notes } = req.body;
        const proof_image = req.file ? `/uploads/${req.file.filename}` : null;

        const checkSql = `SELECT * FROM orders WHERE id = ? AND driver_id = ?`;
        const [orders] = await db.query(checkSql, [orderId, driver_id]);

        if (!orders || orders.length === 0) {
            return res.status(404).json({ success: false, message: 'Pesanan tidak ditemukan' });
        }

        const insertSql = `
            INSERT INTO commission_settlements (
                order_id, driver_id, amount, bank_sender, sender_name, proof_image, notes, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'WAITING_CONFIRMATION')
            ON DUPLICATE KEY UPDATE 
                amount = VALUES(amount),
                bank_sender = VALUES(bank_sender),
                sender_name = VALUES(sender_name),
                proof_image = COALESCE(VALUES(proof_image), proof_image),
                status = 'WAITING_CONFIRMATION'
        `;

        await db.query(insertSql, [
            orderId,
            driver_id,
            amount || orders[0].admin_commission,
            bank_sender || 'BCA',
            sender_name || 'Mitra Sopir',
            proof_image,
            notes || ''
        ]);

        res.json({
            success: true,
            message: 'Konfirmasi transfer komisi berhasil dikirim ke Admin! Terima kasih atas kerjasamanya.'
        });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
}

// 6. Ambil info perjalanan untuk halaman tracking unik konsumen
async function getTrackingInfo(req, res) {
    try {
        const code = req.params.code;
        const sql = `
            SELECT o.*, 
                   d.name AS driver_name, d.phone AS driver_phone,
                   d.car_model, d.car_plate, d.car_color, d.rating AS driver_rating,
                   d.photo_url AS driver_photo
            FROM orders o
            LEFT JOIN drivers d ON o.driver_id = d.id
            WHERE o.tracking_code = ?
        `;
        const rows = await db.query(sql, [code]);

        if (rows.length === 0) {
            return res.status(404).json({ success: false, message: 'Pesanan tidak ditemukan atau link sudah kedaluwarsa.' });
        }

        res.json({ success: true, data: rows[0] });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
}

module.exports = {
    getAuctionOrders,
    createOrder,
    acceptOrder,
    updateTripStatus,
    submitCommissionSettlement,
    getTrackingInfo
};
