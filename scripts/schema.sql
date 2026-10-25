-- SCHEMA DATABASE TRAVEEGO / DROP-OFF.ONLINE
-- Engine: MySQL / MariaDB (InnoDB for ACID Transactions & Row-level Locking)

CREATE DATABASE IF NOT EXISTS traveego_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE traveego_db;

-- 1. TABEL KOTA OPERASIONAL (UNTUK EXPAND EXPANSION TERKENDALI)
CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,          -- 'jakarta', 'bandung', 'surabaya', dll
    province VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,            -- True = aktif dibuka, False = Coming Soon
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. TABEL RUTE & TARIF RESMI (LONG-TAIL DROPOFF)
CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin_city_id INT NOT NULL,
    destination_city_id INT NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,         -- 'jakarta-bandung', 'bandara-soetta-bandung'
    title VARCHAR(150) NOT NULL,               -- 'Drop Off Jakarta ke Bandung'
    distance_km INT DEFAULT 150,
    estimated_hours DECIMAL(3,1) DEFAULT 3.0,
    base_fare DECIMAL(12,2) NOT NULL,          -- Tarif Dasar (Avanza/Xpander)
    innova_fare DECIMAL(12,2) NOT NULL,        -- Tarif Innova Reborn
    hiace_fare DECIMAL(12,2) NOT NULL,         -- Tarif Hiace Commuter
    admin_commission_pct INT DEFAULT 10,       -- Persentase Komisi Admin (misal 10%)
    is_popular BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (origin_city_id) REFERENCES cities(id) ON DELETE CASCADE,
    FOREIGN KEY (destination_city_id) REFERENCES cities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. TABEL MITRA SOPIR MANDIRI (DENGAN MOBIL SENDIRI)
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,         -- Nomor WA Sopir (untuk login PWA)
    pin VARCHAR(6) NOT NULL DEFAULT '1234',    -- PIN cepat 4-6 angka
    city_id INT NOT NULL,                      -- Kota domisili/pangkalan
    car_model VARCHAR(50) NOT NULL,            -- Misal: Avanza, Innova Reborn, Hiace
    car_plate VARCHAR(20) NOT NULL,            -- Misal: B 1823 KBC, D 1422 AC
    car_color VARCHAR(30) DEFAULT 'Hitam',
    photo_url VARCHAR(255) DEFAULT '/assets/driver-avatar.png',
    is_online BOOLEAN DEFAULT TRUE,            -- Status siap narik
    is_trusted BOOLEAN DEFAULT TRUE,           -- True = Pasca-Bayar transfer, False = Wajib Deposit
    rating DECIMAL(2,1) DEFAULT 4.9,
    total_trips INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id)
) ENGINE=InnoDB;

-- 4. TABEL PESANAN (CORE TRANSAKSI ON-DEMAND)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_code VARCHAR(20) NOT NULL UNIQUE, -- Kode unik link tracking pelanggan (TRX-9821)
    route_id INT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(25) NOT NULL,       -- WA Pelanggan
    pickup_address TEXT NOT NULL,
    destination_address TEXT NOT NULL,
    pickup_datetime DATETIME NOT NULL,
    car_type VARCHAR(50) NOT NULL DEFAULT 'Avanza/Xpander',
    passenger_count INT DEFAULT 2,
    notes TEXT,
    
    total_fare DECIMAL(12,2) NOT NULL,         -- Tarif yang dibayar cash pelanggan ke sopir
    admin_commission DECIMAL(12,2) NOT NULL,   -- Hak bagi hasil admin (misal 10%)
    
    -- STATUS SIKLUS HIDUP PESANAN
    status ENUM('AUCTION', 'ACCEPTED', 'ON_THE_WAY', 'ARRIVED', 'IN_TRIP', 'COMPLETED', 'CANCELLED') DEFAULT 'AUCTION',
    driver_id INT NULL,                        -- ID sopir pemenang lelang
    driver_assigned_at DATETIME NULL,
    completed_at DATETIME NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id)
) ENGINE=InnoDB;

-- 5. TABEL SETORAN KOMISI PASCA-BAYAR (KONFIRMASI TRANSFER KE ADMIN)
CREATE TABLE IF NOT EXISTS commission_settlements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    driver_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    bank_sender VARCHAR(50) NOT NULL,          -- BCA, Mandiri, BRI, dll
    sender_name VARCHAR(100) NOT NULL,
    proof_image VARCHAR(255) NULL,             -- Path foto struk transfer / bukti m-banking
    notes TEXT,
    status ENUM('WAITING_CONFIRMATION', 'VERIFIED_PAID', 'REJECTED') DEFAULT 'WAITING_CONFIRMATION',
    verified_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id)
) ENGINE=InnoDB;

-- 6. TABEL DOMPET SALDO (PONDASI SIAP PAKAI JIKA KELAK AKTIFKAN TOP-UP DEPOSIT)
CREATE TABLE IF NOT EXISTS driver_wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL UNIQUE,
    balance DECIMAL(12,2) DEFAULT 0.00,
    min_balance DECIMAL(12,2) DEFAULT 50000.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
) ENGINE=InnoDB;
