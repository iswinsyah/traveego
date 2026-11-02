-- =========================================================================
-- SCHEMA DATABASE RESMI HOSTINGER DROPOFF.ID
-- Database Name: u829486010_dbasedropoff
-- Engine: MySQL 8.x / MariaDB (InnoDB UTF8MB4)
-- Catatan: Tanpa CREATE DATABASE / USE agar bisa langsung di-import di phpMyAdmin
-- =========================================================================

-- 1. TABEL KOTA OPERASIONAL
CREATE TABLE IF NOT EXISTS `cities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `province` VARCHAR(100) NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABEL RUTE & TARIF RESMI (LONG-TAIL DROPOFF)
CREATE TABLE IF NOT EXISTS `routes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `origin_city_id` INT NOT NULL,
    `destination_city_id` INT NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `title` VARCHAR(150) NOT NULL,
    `distance_km` INT DEFAULT 150,
    `estimated_hours` DECIMAL(3,1) DEFAULT 3.0,
    `base_fare` DECIMAL(12,2) NOT NULL,
    `innova_fare` DECIMAL(12,2) NOT NULL,
    `hiace_fare` DECIMAL(12,2) NOT NULL,
    `admin_commission_pct` INT DEFAULT 10,
    `is_popular` BOOLEAN DEFAULT FALSE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`origin_city_id`) REFERENCES `cities`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`destination_city_id`) REFERENCES `cities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABEL MITRA SOPIR MANDIRI (DENGAN GPS TRACKING LIVE)
CREATE TABLE IF NOT EXISTS `drivers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NULL UNIQUE,
    `phone` VARCHAR(25) NOT NULL UNIQUE,
    `pin` VARCHAR(100) NOT NULL DEFAULT '1234',
    `nik_sim` VARCHAR(50) NULL,
    `city_id` INT NULL,
    `car_model` VARCHAR(100) NOT NULL,
    `car_plate` VARCHAR(25) NOT NULL,
    `car_color` VARCHAR(50) DEFAULT 'Hitam Metalik',
    `photo_url` VARCHAR(255) DEFAULT '/assets/driver-avatar.png',
    `photo_ktp` VARCHAR(255) NULL,
    `photo_sim` VARCHAR(255) NULL,
    `photo_car` VARCHAR(255) NULL,
    `is_online` BOOLEAN DEFAULT TRUE,
    `mode` ENUM('service', 'standby', 'offline') DEFAULT 'standby',
    `is_trusted` BOOLEAN DEFAULT TRUE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `is_super_admin` BOOLEAN DEFAULT FALSE,
    `rating` DECIMAL(2,1) DEFAULT 5.0,
    `total_trips` INT DEFAULT 0,
    `current_lat` DECIMAL(10, 7) DEFAULT -6.2088,
    `current_lng` DECIMAL(10, 7) DEFAULT 106.8456,
    `last_location_update` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABEL PESANAN (CORE TRANSAKSI ON-DEMAND)
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tracking_code` VARCHAR(20) NOT NULL UNIQUE,
    `route_id` INT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(25) NOT NULL,
    `customer_phone_masked` VARCHAR(25) NULL,
    `pickup_address` TEXT NOT NULL,
    `destination_address` TEXT NOT NULL,
    `pickup_datetime` DATETIME NOT NULL,
    `pickup_lat` DECIMAL(10, 7) NULL,
    `pickup_lng` DECIMAL(10, 7) NULL,
    `dest_lat` DECIMAL(10, 7) NULL,
    `dest_lng` DECIMAL(10, 7) NULL,
    `car_type` VARCHAR(50) NOT NULL DEFAULT 'Toyota Innova Reborn',
    `passenger_count` INT DEFAULT 2,
    `notes` TEXT,
    `total_fare` DECIMAL(12,2) NOT NULL,
    `admin_commission` DECIMAL(12,2) NOT NULL,
    `status` ENUM('AUCTION', 'ACCEPTED', 'ON_THE_WAY', 'ARRIVED', 'IN_TRIP', 'COMPLETED', 'CANCELLED') DEFAULT 'AUCTION',
    `driver_id` INT NULL,
    `driver_assigned_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `is_emergency` TINYINT(1) DEFAULT 0,
    `emergency_reason` VARCHAR(255) NULL,
    `emergency_at` DATETIME NULL,
    `cancelled_by` VARCHAR(50) NULL,
    `customer_rating` INT NULL,
    `customer_review` TEXT NULL,
    `review_tags` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABEL SETORAN KOMISI PASCA-BAYAR (KONFIRMASI TRANSFER KE ADMIN)
CREATE TABLE IF NOT EXISTS `commission_settlements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL,
    `driver_id` INT NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `bank_sender` VARCHAR(50) NOT NULL,
    `sender_name` VARCHAR(100) NOT NULL,
    `proof_image` VARCHAR(255) NULL,
    `notes` TEXT,
    `status` ENUM('WAITING_CONFIRMATION', 'VERIFIED_PAID', 'REJECTED') DEFAULT 'WAITING_CONFIRMATION',
    `verified_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABEL PENDAFTARAN MITRA SOPIR (SIGNUP MENUNGGU VERIFIKASI)
CREATE TABLE IF NOT EXISTS `pending_drivers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reg_id` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nik_sim` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(25) NOT NULL,
    `car_model` VARCHAR(100) NOT NULL,
    `car_plate` VARCHAR(25) NOT NULL,
    `photo_ktp` VARCHAR(255) NULL,
    `photo_sim` VARCHAR(255) NULL,
    `photo_car` VARCHAR(255) NULL,
    `status` ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- SEED DATA AWAL (KOTA, RUTE RESMI, DRIVER SUPER ADMIN & ORDER RADAR)
-- =========================================================================

-- Seed Kota
INSERT INTO `cities` (`id`, `name`, `slug`, `province`, `is_active`) VALUES
(1, 'Jakarta', 'jakarta', 'DKI Jakarta', 1),
(2, 'Bandung', 'bandung', 'Jawa Barat', 1),
(3, 'Bogor', 'bogor', 'Jawa Barat', 1),
(4, 'Depok', 'depok', 'Jawa Barat', 1),
(5, 'Tangerang', 'tangerang', 'Banten', 1),
(6, 'Bekasi', 'bekasi', 'Jawa Barat', 1),
(7, 'Cirebon', 'cirebon', 'Jawa Barat', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed Rute Resmi
INSERT INTO `routes` (`id`, `origin_city_id`, `destination_city_id`, `slug`, `title`, `distance_km`, `estimated_hours`, `base_fare`, `innova_fare`, `hiace_fare`, `is_popular`) VALUES
(1, 1, 2, 'jakarta-bandung', 'Drop Off Jakarta ke Bandung', 150, 2.5, 750000, 1050000, 1600000, 1),
(2, 5, 2, 'bandara-soetta-bandung', 'Drop Off Bandara Soetta ke Bandung', 175, 3.0, 850000, 1150000, 1750000, 1),
(3, 1, 3, 'jakarta-bogor', 'Drop Off Jakarta ke Bogor', 60, 1.2, 450000, 650000, 1100000, 0),
(4, 2, 1, 'bandung-jakarta', 'Drop Off Bandung ke Jakarta', 150, 2.5, 750000, 1050000, 1600000, 1)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- Seed Akun Sopir Super Admin (Winsyah)
INSERT INTO `drivers` (`id`, `name`, `username`, `phone`, `pin`, `car_model`, `car_plate`, `car_color`, `is_super_admin`, `rating`, `total_trips`) VALUES
(999, 'Winsyah (Super Admin)', 'winsyah', '081234567891', '1234', 'Toyota HiAce Premio Luxury', 'B 1924 WIN', 'Hitam Metalik', 1, 5.0, 48)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed Pesanan Radar Awal (Sesuai Mockup)
INSERT INTO `orders` (`id`, `tracking_code`, `route_id`, `customer_name`, `customer_phone`, `customer_phone_masked`, `pickup_address`, `destination_address`, `pickup_datetime`, `pickup_lat`, `pickup_lng`, `dest_lat`, `dest_lng`, `car_type`, `passenger_count`, `total_fare`, `admin_commission`, `status`) VALUES
(1, 'TRX-9903', 1, 'Ibu Dr. Ratna', '081399887766', '0813-****-7766', 'Halim Perdanakusuma Airport (HLP)', 'Ciumbuleuit, Cidadap, Kota Bandung', '2026-11-03 04:30:00', -6.2666000, 106.8911000, -6.8722000, 107.6044000, 'Toyota HiAce Premio', 6, 1600000.00, 160000.00, 'AUCTION'),
(2, 'TRX-8821', 2, 'Ibu Maya Lestari', '081987654321', '0819-****-4321', 'Terminal 3 Kedatangan Internasional Bandara Soetta', 'Jl. Dago No. 120, Coblong, Kota Bandung', '2026-11-02 08:30:00', -6.1264000, 106.6559000, -6.8858000, 107.6142000, 'Toyota Innova Reborn', 3, 1050000.00, 105000.00, 'AUCTION'),
(3, 'TRX-7712', 1, 'Bpk. Handoko', '081233445566', '0812-****-5566', 'Kuningan City Mall, Jakarta Selatan', 'Jl. Pasteur No. 25, Sukajadi, Bandung', '2026-11-02 07:45:00', -6.2246000, 106.8298000, -6.8996000, 107.5922000, 'Toyota All New Avanza', 2, 700000.00, 70000.00, 'AUCTION')
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`);
