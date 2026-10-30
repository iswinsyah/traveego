require('dotenv').config();
const express = require('express');
const http = require('http');
const path = require('path');
const cors = require('cors');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

// Inisialisasi Socket.io untuk lelang real-time
const io = new Server(server, {
    cors: {
        origin: '*',
        methods: ['GET', 'POST']
    }
});

const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Sajikan folder uploads foto struk transfer
app.use('/uploads', express.static(path.join(__dirname, '../uploads')));

// Sajikan folder public untuk aset frontend PWA & Web
app.use(express.static(path.join(__dirname, '../public')));

// Pasang rute API
const apiRoutes = require('./routes/api')(io);
app.use('/api', apiRoutes);

// --- RUTE HALAMAN FRONTEND ---
// 1. Halaman Pelanggan (Landing Page & Form Booking)
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, '../public/index.html'));
});

// 2. Halaman Live Tracking Unik Pelanggan (Magic Link)
app.get('/trip/:code', (req, res) => {
    res.sendFile(path.join(__dirname, '../public/trip.html'));
});

// 3. PWA Mitra Sopir Mandiri
app.get('/driver', (req, res) => {
    res.sendFile(path.join(__dirname, '../public/driver.html'));
});

// 4. Dashboard Operasional Admin & CS
app.get('/admin', (req, res) => {
    res.sendFile(path.join(__dirname, '../public/admin.html'));
});

// Socket.io Real-time Connection Handler
io.on('connection', (socket) => {
    console.log('⚡ Client terhubung via WebSocket:', socket.id);

    // Bergabung ke room tracking pesanan tertentu
    socket.on('join_tracking', (trackingCode) => {
        socket.join(`track_${trackingCode}`);
        console.log(`📡 Client bergabung ke tracking room: track_${trackingCode}`);
    });

    socket.on('disconnect', () => {
        // Disconnected
    });
});

// Start Server
server.listen(PORT, () => {
    console.log(`
=====================================================
🚀 DROPOFF.ID PLATFORM BERJALAN DENGAN SUKSES!
=====================================================
🌐 Portal Web Pelanggan : http://localhost:${PORT}/
🚘 PWA Mitra Sopir      : http://localhost:${PORT}/driver
👑 Dashboard Admin      : http://localhost:${PORT}/admin
⚡ WebSocket Real-time   : Aktif di port ${PORT}
=====================================================
    `);
});
