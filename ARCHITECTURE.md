# CETAK BIRU ARSITEKTUR PLATFORM TRAVEEGO DROP-OFF
> **Dokumen Resmi Arsitektur Sistem, Model Bisnis, Skema Database, dan SOP Deployment VPS**  
> *Versi: 1.0.0 &bull; Pembaruan Terakhir: Oktober 2026*  
> *Repositori GitHub:* [https://github.com/iswinsyah/traveego](https://github.com/iswinsyah/traveego)

---

## 1. RINGKASAN EKSEKUTIF & MODEL BISNIS

### 1.1. Konsep Bisnis: Asset-Light On-Demand Marketplace
Traveego bertransformasi dari rental mobil konvensional (titipan mobil investor) menjadi **marketplace transportasi antarkota berbasis armada mandiri** (seperti Grab/GoCar/InDrive untuk rute luar kota dan bandara).
* **Mitra Sopir Mandiri:** Sopir mengemudikan kendaraan milik mereka sendiri (Avanza, Innova Reborn, Hiace).
* **Bebas Biaya Perawatan Unit:** Perawatan, bahan bakar, depresiasi, dan risiko armada 100% berada pada mitra. Platform fokus pada akuisisi pelanggan (SEO/Google Rank 1) dan sistem operasional lelang.
* **Model Pendapatan (Komisi Bagi Hasil):**
  - Pelanggan membayar **100% Cash** ke sopir setelah perjalanan selesai.
  - Sopir menyetorkan komisi bagi hasil admin (rata-rata **10%**) ke rekening bank admin Traveego via transfer.
  - Didukung sistem konfirmasi pasca-bayar (*post-paid settlement*) dengan upload foto struk m-banking.

### 1.2. Hub Operasional Geografis
* **Hub Barat (Jawa Barat & Jabodetabek):** Dikelola oleh Co-Founder di **Bandung**.
  - *Koridor Utama:* Jakarta &ndash; Bandung, Bandara Soekarno-Hatta &ndash; Bandung, dan sekitarnya.
* **Hub Timur (Jawa Timur):** Dikelola oleh Founder di **Batu / Malang**.
  - *Koridor Utama (Fase 2):* Surabaya (Bandara Juanda) &ndash; Malang &ndash; Kota Batu.
* **Strategi "Muatan Balikan" (Backhaul):** Sopir yang baru mengantar tamu ke luar kota berebut mengambil order lelang kembali ke kota asal agar mobil tidak pulang dalam keadaan kosong.

---

## 2. STRATEGI DOMINASI SEO & IDENTITAS DOMAIN

### 2.1. Otoritas Induk (The Mothership 2014)
* **Aset Otoritas:** `rentalmobiljakartabandung.com` (Halaman 1 Rank 1 Google sejak 2014).
* **Strategi Sundulan (Topical Authority Transfer):** Memberikan *contextual backlink* dan banner rekomendasi dari web lama ke domain platform baru untuk mentransfer reputasi PageRank tanpa terkena penalti Google.

### 2.2. Domain Baru untuk Layanan Drop-Off
* **Target Domain Utama:** 
  - Opsi Utama: **`dropoff.id`** (Sangat prestisius, kasta tertinggi kepercayaan konsumen Indonesia, sinyal lokal terkuat di Google.co.id).
  - Opsi Skalabel: **`drop-off.online`** (Topical exact match, netral untuk ekspansi nasional).
* **Struktur URL Bersarang (Nested Subdirectory):**
  - Hub Wilayah: `/jakarta`, `/bandung`, `/surabaya`
  - Rute Long-Tail Spesifik:
    - `/jakarta-bandung` &rarr; Target: *"Drop off Jakarta Bandung"*
    - `/bandara-soetta-bandung` &rarr; Target: *"Drop off Bandara Soetta ke Bandung"*
    - `/surabaya-malang` &rarr; Target: *"Drop off Surabaya Malang"*

---

## 3. SISTEM ANTI-BAJAK PELANGGAN (ZERO DISINTERMEDIATION)

Kekhawatiran terbesar dalam bisnis transportasi perantara adalah **sopir membajak nomor kontak pelanggan untuk transaksi di luar aplikasi (*bypassing*)**.

### Solusi Proteksi Traveego:
1. **Formulir Web Menangkap Nomor WhatsApp Pelanggan:**
   - Nomor WhatsApp pelanggan **HANYA TERSIMPAN DI DATABASE ADMIN**.
   - Digunakan untuk arsip CRM, rekam jejak, dan broadcast promo resmi perusahaan.
2. **Sensor Nomor HP di Aplikasi PWA Sopir (*Number Masking*):**
   - Layar PWA sopir **TIDAK MENAMPILKAN nomor asli**.
   - Ditampilkan dengan sensor: `Ibu Maya Lestari (0819-****-4321)`.
   - Sopir tidak memiliki akses link direct WhatsApp ke tamu.
3. **Koordinasi Wajib via In-App Live Chat (Di Dalam Web/PWA):**
   - Koordinasi penjemputan (*"Pak saya sudah di lobi"*) berjalan melalui **WebSocket Traveego** secara internal.
   - Tidak ada pertukaran kontak pribadi.
4. **Audit Trail Admin:**
   - Seluruh percakapan tercatat di sistem agar admin bisa memantau dan memberi sanksi tegas jika ada sopir yang mengajak transaksi di luar platform.

---

## 4. TIGA PINTU PENGALAMAN PENGGUNA (ROLE ARCHITECTURE)

```mermaid
flowchart TD
    subgraph Pintu1 [1. Pintu Pelanggan]
        Web[/ dan /trip/TRX-XXXX]
        Web -->|Tanpa Login & Tanpa Install| UX1[Mobile-First Booking & Live Tracking Map]
    end

    subgraph Pintu2 [2. Pintu Mitra Sopir]
        Driver[/driver]
        Driver -->|PWA Standalone & Login WA+PIN| UX2[Radar Lelang Real-Time & Setoran Komisi]
    end

    subgraph Pintu3 [3. Pintu Admin]
        Admin[/admin]
        Admin -->|Dashboard PC/HP| UX3[Monitoring Armada & Verifikasi Struk 1-Klik]
    end
```

### A. Pintu Pelanggan (`/` & `/trip/:trackingCode`)
* **Filosofi: Zero Friction.** Pelanggan malas login dan malas instal aplikasi untuk sekali jalan.
* Tampilan berstandar aplikasi mobile (Android-friendly) dengan kartu mobil yang bisa digeser dan tombol aksi melayang (*sticky bottom bar*).
* Selesai pesan, pelanggan mendapat **Magic Link** unik (`/trip/TRX-XXXX`) untuk memantau status mobil di peta tanpa login.

### B. Pintu Mitra Sopir (`/driver`)
* **Filosofi: Work Tool.** Sopir wajib pasang PWA di layar utama HP (*Add to Home Screen*) untuk mencari nafkah.
* **Login Cepat:** Nomor WhatsApp + PIN 4 Digit (Default: `1234`).
* **Radar Lelang (Siapa Cepat Dia Dapat):** Suara notifikasi *ting-ting!* saat ada order baru.
* **Penguncian Atomik (Atomic Lock):** Mencegah 2 sopir menang di milidetik yang sama menggunakan query MySQL:
  ```sql
  UPDATE orders 
  SET driver_id = :driverId, status = 'ACCEPTED', driver_assigned_at = NOW() 
  WHERE id = :orderId AND status = 'AUCTION' AND driver_id IS NULL;
  ```
* **Form Setoran Pasca-Bayar:** Tampil nomor rekening admin, tombol salin nomor rekening, dan upload foto screenshot struk m-banking.

### C. Pintu Admin & CS Console (`/admin`)
* Menampilkan metrik transaksi (Total Order, Lelang Aktif, Order Sukses, Total Komisi Masuk).
* Tabel verifikasi setoran komisi: admin melihat foto struk dan klik **"✅ Konfirmasi Lunas"** dalam 1 detik.

---

## 5. SKEMA DATABASE MYSQL (`traveego_db`)

Database dirancang menggunakan engine **InnoDB** dengan relasi relasional penuh dan integritas data ACID:

```sql
-- 1. KOTA
CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    province VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. RUTE & TARIF RESMI
CREATE TABLE routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin_city_id INT NOT NULL,
    destination_city_id INT NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    distance_km INT DEFAULT 150,
    estimated_hours DECIMAL(3,1) DEFAULT 3.0,
    base_fare DECIMAL(12,2) NOT NULL,
    innova_fare DECIMAL(12,2) NOT NULL,
    hiace_fare DECIMAL(12,2) NOT NULL,
    admin_commission_pct INT DEFAULT 10,
    is_popular BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (origin_city_id) REFERENCES cities(id),
    FOREIGN KEY (destination_city_id) REFERENCES cities(id)
) ENGINE=InnoDB;

-- 3. MITRA SOPIR MANDIRI
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    pin VARCHAR(6) NOT NULL DEFAULT '1234',
    city_id INT NOT NULL,
    car_model VARCHAR(50) NOT NULL,
    car_plate VARCHAR(20) NOT NULL,
    car_color VARCHAR(30) DEFAULT 'Hitam',
    photo_url VARCHAR(255) DEFAULT '/assets/driver-avatar.png',
    is_online BOOLEAN DEFAULT TRUE,
    is_trusted BOOLEAN DEFAULT TRUE,
    rating DECIMAL(2,1) DEFAULT 4.9,
    total_trips INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id)
) ENGINE=InnoDB;

-- 4. TRANSAKSI PESANAN
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_code VARCHAR(20) NOT NULL UNIQUE,
    route_id INT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(25) NOT NULL,
    pickup_address TEXT NOT NULL,
    destination_address TEXT NOT NULL,
    pickup_datetime DATETIME NOT NULL,
    car_type VARCHAR(50) NOT NULL DEFAULT 'Avanza/Xpander',
    passenger_count INT DEFAULT 2,
    notes TEXT,
    total_fare DECIMAL(12,2) NOT NULL,
    admin_commission DECIMAL(12,2) NOT NULL,
    status ENUM('AUCTION', 'ACCEPTED', 'ON_THE_WAY', 'ARRIVED', 'IN_TRIP', 'COMPLETED', 'CANCELLED') DEFAULT 'AUCTION',
    driver_id INT NULL,
    driver_assigned_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id)
) ENGINE=InnoDB;

-- 5. SETORAN KOMISI PASCA-BAYAR
CREATE TABLE commission_settlements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    driver_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    bank_sender VARCHAR(50) NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    proof_image VARCHAR(255) NULL,
    notes TEXT,
    status ENUM('WAITING_CONFIRMATION', 'VERIFIED_PAID', 'REJECTED') DEFAULT 'WAITING_CONFIRMATION',
    verified_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id)
) ENGINE=InnoDB;
```

---

## 6. PANDUAN LENGKAP DEPLOYMENT VPS HELIPOD.IO (1 GB RAM)

VPS Helipod.io berlokasi di **Data Center Indonesia (Jakarta / IIX)** dengan harga $\pm$ Rp 61.000/bulan. Berikut adalah panduan instalasi dari nol hingga aplikasi online 24 jam nonstop.

### Langkah 1: Login SSH ke VPS
Buka terminal komputer Anda:
```bash
ssh root@[IP_VPS_HELIPOD_ANDA]
```

### Langkah 2: Pasang 2 GB SWAP Memory (Wajib untuk VPS 1 GB RAM)
Mencegah server *crash* atau *Out of Memory* saat menangani traffic lelang:
```bash
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

### Langkah 3: Update Server & Install Node.js, Git, Nginx, MySQL
```bash
apt update && apt upgrade -y
apt install -y curl git nginx mysql-server

# Pasang Node.js v20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Pasang PM2 (Process Manager agar web jalan 24 jam nonstop)
npm install -g pm2
```

### Langkah 4: Konfigurasi Database MySQL di VPS
Masuk ke terminal MySQL:
```bash
mysql -u root
```
Ketik perintah SQL berikut di dalam MySQL:
```sql
CREATE DATABASE traveego_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'traveego_user'@'localhost' IDENTIFIED BY 'PasswordKuatTraveego2026!';
GRANT ALL PRIVILEGES ON traveego_db.* TO 'traveego_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Langkah 5: Clone Project dari GitHub
```bash
cd /var/www
git clone https://github.com/iswinsyah/traveego.git
cd traveego

# Install dependencies
npm install
```

### Langkah 6: Konfigurasi File `.env` di VPS
Buat file `.env` di dalam folder `/var/www/traveego`:
```bash
nano .env
```
Isi dengan setelan produksi VPS:
```ini
PORT=3000
NODE_ENV=production

DB_HOST=localhost
DB_PORT=3306
DB_USER=traveego_user
DB_PASSWORD=PasswordKuatTraveego2026!
DB_NAME=traveego_db

ADMIN_BANK_NAME=BCA
ADMIN_BANK_ACCOUNT=1234567890
ADMIN_BANK_HOLDER=Traveego Operasional

BASE_URL=https://dropoff.id
```
Simpan dengan menekan `Ctrl + O` lalu `Enter`, kemudian keluar dengan `Ctrl + X`.

### Langkah 7: Inisialisasi Database (Otomatis)
Jalankan script inisialisasi tabel dan data awal:
```bash
npm run init-db
```

### Langkah 8: Jalankan Aplikasi dengan PM2
```bash
pm2 start src/server.js --name traveego
pm2 save
pm2 startup
```

### Langkah 9: Konfigurasi Nginx Reverse Proxy & SSL (Gembok Hijau)
Buat konfigurasi Nginx untuk domain Anda:
```bash
nano /etc/nginx/sites-available/traveego
```
Isi dengan konfigurasi berikut:
```nginx
server {
    listen 80;
    server_name dropoff.id www.dropoff.id; # atau drop-off.online

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```
Aktifkan dan restart Nginx:
```bash
ln -s /etc/nginx/sites-available/traveego /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

Pasang SSL gratis dari Let's Encrypt:
```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d dropoff.id -d www.dropoff.id
```

**Selesai!** Platform Traveego Drop-Off resmi aktif di internet, dilindungi HTTPS, dan berjalan 24 jam nonstop di VPS Helipod Indonesia.

---

## 7. STRUKTUR FOLDER PROYEK

```text
traveego/
├── .env                      # File rahasia kredensial (tidak di-upload ke git)
├── .env.example              # Template contoh konfigurasi
├── .gitignore                # Filter keamanan berkas
├── package.json              # Daftar pustaka Node.js (Express, MySQL2, Socket.io)
├── ARCHITECTURE.md           # Cetak biru resmi arsitektur sistem
│
├── scripts/
│   ├── schema.sql            # Definisi 6 tabel inti database InnoDB
│   ├── seed.sql              # Data awal kota, rute koridor emas, & sopir contoh
│   └── init-db.js            # Script eksekusi migrasi database 1-klik
│
├── src/
│   ├── config/db.js          # MySQL connection pool hemat memori (maks 15 koneksi)
│   ├── controllers/          # Logika bisnis lelang atomik, sopir, admin, komisi
│   ├── services/aiAgent.js   # Integrasi CS AI lokal (Ollama / Qwen) & fallback
│   ├── routes/api.js         # Endpoint REST API & handler upload bukti transfer
│   └── server.js             # Entrypoint server Express + Socket.io realtime
│
└── public/
    ├── index.html            # Portal pelanggan (Mobile-First Android Friendly)
    ├── trip.html             # Live tracking satelit pelanggan (Magic Link)
    ├── driver.html           # Aplikasi PWA khusus mitra sopir mandiri
    ├── admin.html            # Konsol operasional & verifikasi setoran admin
    ├── manifest.json         # Manifest PWA install homescreen
    └── sw.js                 # Service worker PWA offline worker
```

---
*Dokumen ini dibuat secara otomatis sebagai panduan standar arsitektur dan kelangsungan operasional platform Traveego.*
