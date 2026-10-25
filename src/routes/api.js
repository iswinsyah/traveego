const express = require('express');
const router = express.Router();
const path = require('path');
const multer = require('multer');
const fs = require('fs');

const orderController = require('../controllers/orderController');
const driverController = require('../controllers/driverController');
const adminController = require('../controllers/adminController');
const aiAgent = require('../services/aiAgent');

// Pastikan folder uploads ada
const uploadDir = path.join(__dirname, '../../uploads');
if (!fs.existsSync(uploadDir)) {
    fs.mkdirSync(uploadDir, { recursive: true });
}

// Konfigurasi Multer untuk upload foto struk bukti transfer
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        cb(null, uploadDir);
    },
    filename: (req, file, cb) => {
        const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
        const ext = path.extname(file.originalname) || '.jpg';
        cb(null, 'struk-' + uniqueSuffix + ext);
    }
});
const upload = multer({ 
    storage,
    limits: { fileSize: 5 * 1024 * 1024 } // maks 5MB
});

module.exports = function(io) {
    // --- 1. CONFIG INFO (REKENING ADMIN UNTUK SETORAN) ---
    router.get('/config', (req, res) => {
        res.json({
            success: true,
            adminBank: {
                bank: process.env.ADMIN_BANK_NAME || 'BCA',
                account: process.env.ADMIN_BANK_ACCOUNT || '1234567890',
                holder: process.env.ADMIN_BANK_HOLDER || 'Traveego Operasional'
            }
        });
    });

    // --- 2. ORDER & LELANG ---
    router.get('/orders/auction', orderController.getAuctionOrders);
    router.post('/orders', (req, res) => orderController.createOrder(req, res, io));
    router.post('/orders/:id/accept', (req, res) => orderController.acceptOrder(req, res, io));
    router.post('/orders/:id/status', (req, res) => orderController.updateTripStatus(req, res, io));
    router.post('/orders/:id/settle-commission', upload.single('proof_image'), orderController.submitCommissionSettlement);
    router.get('/orders/track/:code', orderController.getTrackingInfo);

    // --- 3. DRIVER MITRA PWA ---
    router.post('/driver/login', driverController.login);
    router.get('/driver/:id/history', driverController.getDriverHistory);

    // --- 4. ADMIN DASHBOARD ---
    router.get('/admin/stats', adminController.getDashboardStats);
    router.post('/admin/settlements/:id/verify', adminController.verifySettlement);

    // --- 5. AGENTIC CS AI CHAT ---
    router.post('/ai/chat', async (req, res) => {
        try {
            const { message, history } = req.body;
            if (!message) {
                return res.status(400).json({ success: false, message: 'Pesan tidak boleh kosong' });
            }
            const reply = await aiAgent.chatWithAI(message, history || []);
            res.json({ success: true, reply });
        } catch (err) {
            res.status(500).json({ success: false, message: err.message });
        }
    });

    return router;
};
