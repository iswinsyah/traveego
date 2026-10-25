require('dotenv').config();
const mysql = require('mysql2/promise');

// Buat Connection Pool untuk efisiensi RAM dan koneksi cepat
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    port: parseInt(process.env.DB_PORT || '3306'),
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'traveego_db',
    waitForConnections: true,
    connectionLimit: 15,          // Aman untuk shared hosting & VPS 1GB
    queueLimit: 0,
    enableKeepAlive: true,
    keepAliveInitialDelay: 10000
});

// Helper query function
async function query(sql, params) {
    try {
        const [results] = await pool.execute(sql, params);
        return results;
    } catch (error) {
        console.error('Database Query Error:', error.message, 'SQL:', sql);
        throw error;
    }
}

module.exports = {
    pool,
    query
};
