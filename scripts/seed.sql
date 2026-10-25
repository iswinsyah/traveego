-- DATA AWAL (SEED) UNTUK INISIALISASI TRAVEEGO
USE traveego_db;

-- 1. DATA KOTA
INSERT IGNORE INTO cities (id, name, slug, province, is_active) VALUES
(1, 'Jakarta', 'jakarta', 'DKI Jakarta', 1),
(2, 'Bandung', 'bandung', 'Jawa Barat', 1),
(3, 'Bandara Soekarno-Hatta', 'soetta', 'Banten', 1),
(4, 'Surabaya', 'surabaya', 'Jawa Timur', 1),
(5, 'Malang & Batu', 'malang', 'Jawa Timur', 1);

-- 2. DATA RUTE KORIDOR EMAS (DROP-OFF ALL-IN)
INSERT IGNORE INTO routes (id, origin_city_id, destination_city_id, slug, title, distance_km, estimated_hours, base_fare, innova_fare, hiace_fare, admin_commission_pct, is_popular, is_active) VALUES
(1, 1, 2, 'jakarta-bandung', 'Drop Off Jakarta ke Bandung', 150, 2.5, 700000.00, 950000.00, 1600000.00, 10, 1, 1),
(2, 3, 2, 'bandara-soetta-bandung', 'Drop Off Bandara Soekarno-Hatta ke Bandung', 175, 3.0, 750000.00, 1050000.00, 1750000.00, 10, 1, 1),
(3, 2, 1, 'bandung-jakarta', 'Drop Off Bandung ke Jakarta', 150, 2.5, 700000.00, 950000.00, 1600000.00, 10, 1, 1),
(4, 2, 3, 'bandung-bandara-soetta', 'Drop Off Bandung ke Bandara Soekarno-Hatta', 175, 3.0, 750000.00, 1050000.00, 1750000.00, 10, 1, 1),
(5, 4, 5, 'surabaya-malang', 'Drop Off Surabaya ke Malang / Batu', 95, 2.0, 450000.00, 650000.00, 1100000.00, 10, 1, 1);

-- 3. DATA MITRA SOPIR MANDIRI (CONTOH MITRA TERPERCAYA)
INSERT IGNORE INTO drivers (id, name, phone, pin, city_id, car_model, car_plate, car_color, is_online, is_trusted, rating, total_trips) VALUES
(1, 'Pak Budi Santoso', '081234567891', '1234', 2, 'Toyota Avanza Veloz', 'B 1823 KBC', 'Putih', 1, 1, 4.9, 142),
(2, 'Kang Asep Supriatna', '081234567892', '1234', 2, 'Toyota Innova Reborn', 'D 1422 AC', 'Hitam', 1, 1, 5.0, 218),
(3, 'Pak Hendra Gunawan', '081234567893', '1234', 1, 'Mitsubishi Xpander', 'B 2901 ZAA', 'Silver', 1, 1, 4.8, 97);

-- 4. CONTOH PESANAN AKTIF SIAP LELANG (STATUS: AUCTION)
INSERT IGNORE INTO orders (id, tracking_code, route_id, customer_name, customer_phone, pickup_address, destination_address, pickup_datetime, car_type, passenger_count, notes, total_fare, admin_commission, status) VALUES
(1, 'TRX-8821', 2, 'Ibu Maya Lestari', '081987654321', 'Terminal 3 Kedatangan Internasional Bandara Soetta', 'Jl. Dago No. 120, Coblong, Bandung', DATE_ADD(NOW(), INTERVAL 2 HOUR), 'Toyota Innova Reborn', 3, 'Ada 3 koper besar, mohon mobil bersih dan wangi', 1050000.00, 105000.00, 'AUCTION'),
(2, 'TRX-7712', 1, 'Bpk. Handoko', '081233445566', 'Kuningan City, Jakarta Selatan', 'Pasteur, Sukajadi, Kota Bandung', DATE_ADD(NOW(), INTERVAL 4 HOUR), 'Toyota Avanza Veloz', 2, 'Jemput lobi utama Kuningan City', 700000.00, 70000.00, 'AUCTION');
