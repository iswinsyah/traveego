# 🌊 Dropoff.id Drop-Off Platform

> Platform sewa mobil khusus **Drop-Off Antarkota & Bandara** sekali jalan berbasis armada mitra sopir mandiri, lelang *real-time* PWA, dan sistem anti-bajak pelanggan.

[![Node.js](https://img.shields.io/badge/Node.js-v20%2B-green.svg)](https://nodejs.org/)
[![Database](https://img.shields.io/badge/Database-MySQL%20InnoDB-blue.svg)](https://www.mysql.com/)
[![PWA](https://img.shields.io/badge/PWA-Mobile--First-orange.svg)](https://web.dev/progressive-web-apps/)
[![License](https://img.shields.io/badge/License-ISC-purple.svg)]()

---

## 📖 Dokumentasi Lengkap
Seluruh cetak biru sistem, model bisnis marketplace antarkota, skema database, strategi SEO Google Rank 1, serta panduan instalasi di VPS Helipod.io dapat dibaca secara detail di:
👉 **[Lihat Dokumen Cetak Biru Lengkap: ARCHITECTURE.md](./ARCHITECTURE.md)**

---

## 🚀 Fitur Utama

1. **Pintu Pelanggan (`/` dan `/trip/:code`):**
   - Tampilan *Android / Mobile-First App Experience* yang ergonomis untuk layar HP.
   - Tanpa login, tanpa ribet instal aplikasi (*Zero Friction*).
   - Form booking cepat + *Magic Link* live tracking mobil dan sopir.
2. **Pintu Mitra Sopir Mandiri (`/driver`):**
   - Progressive Web App (PWA) yang dapat dipasang di layar utama ponsel (*Add to Home Screen*).
   - Radar lelang pesanan *real-time* ("Siapa Cepat Dia Dapat") berproteksi **MySQL Atomic Lock** (anti-bentrok).
   - Form konfirmasi setoran komisi pasca-bayar lengkap dengan fitur salin rekening admin dan upload struk m-banking.
3. **Pintu Konsol Operasional Admin (`/admin`):**
   - Dashboard statistik transaksi harian dan pemantauan pergerakan armada.
   - Verifikasi bukti transfer komisi sopir dalam 1-klik (*"Konfirmasi Lunas"*).
4. **Sistem Anti-Bajak Pelanggan (*Zero Disintermediation*):**
   - Nomor WhatsApp pelanggan hanya tersimpan di database admin (disensor di layar PWA sopir).
   - Koordinasi penjemputan berjalan melalui *In-App Chat* internal Dropoff.id.

---

## 💻 Menjalankan di Komputer Lokal

```bash
# 1. Clone repository
git clone https://github.com/iswinsyah/dropoff.id.git
cd dropoff.id

# 2. Install dependensi
npm install

# 3. Siapkan file .env (copy dari .env.example)
cp .env.example .env

# 4. Inisialisasi Database MySQL lokal (pastikan Laragon / XAMPP sudah aktif)
npm run init-db

# 5. Jalankan server
npm start
```
Buka browser di:
- 🌐 Pelanggan : `http://localhost:3000/`
- 🚘 Mitra Sopir: `http://localhost:3000/driver` (Login: `081234567891` | PIN: `1234`)
- 👑 Admin Ops  : `http://localhost:3000/admin`

---
*Dikembangkan untuk mendominasi pasar transportasi Drop-Off Jakarta &ndash; Bandung dan antarkota di Indonesia.*
