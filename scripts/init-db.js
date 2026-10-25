require('dotenv').config();
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

async function initDatabase() {
    console.log('🔄 Memulai inisialisasi database Traveego...');
    
    // 1. Koneksi awal ke server MySQL (tanpa memilih database dulu)
    let connection;
    try {
        connection = await mysql.createConnection({
            host: process.env.DB_HOST || 'localhost',
            port: parseInt(process.env.DB_PORT || '3306'),
            user: process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || '',
            multipleStatements: true
        });
        console.log('✅ Terhubung ke MySQL server.');
    } catch (err) {
        console.error('❌ Gagal terhubung ke MySQL:', err.message);
        console.error('💡 TIP: Pastikan Laragon / XAMPP sudah Anda klik "Start" dan port 3306 aktif.');
        process.exit(1);
    }

    try {
        // 2. Baca file schema.sql dan eksekusi
        const schemaPath = path.join(__dirname, 'schema.sql');
        const schemaSql = fs.readFileSync(schemaPath, 'utf8');
        console.log('⏳ Membuat database dan tabel-tabel...');
        await connection.query(schemaSql);
        console.log('✅ Database `traveego_db` dan tabel berhasil dibuat!');

        // 3. Baca file seed.sql dan eksekusi
        const seedPath = path.join(__dirname, 'seed.sql');
        const seedSql = fs.readFileSync(seedPath, 'utf8');
        console.log('⏳ Memasukkan data awal (Kota, Rute, Sopir, Order lelang)...');
        await connection.query(seedSql);
        console.log('✅ Data awal berhasil di-seed!');

        console.log('\n🎉 INISIALISASI DATABASE SELESAI!');
        console.log('📋 Anda sekarang bisa login di PWA Driver dengan No WA: 081234567891 (PIN: 1234)');
    } catch (err) {
        console.error('❌ Error saat mengeksekusi SQL:', err.message);
    } finally {
        await connection.end();
    }
}

initDatabase();
