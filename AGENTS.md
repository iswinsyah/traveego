# SOP & PEDOMAN PENGEMBANGAN TRAVEEGO

## 1. SOP OTOMATISASI DEPLOYMENT (WAJIB DIIKUTI)
- **Setiap ada perubahan kode, perbaikan bug, atau penambahan fitur**, agent WAJIB langsung:
  1. Melakukan validasi lokal (misal: cek sintaks PHP `php -l`).
  2. Melakukan `git add .` dan `git commit -m "..."`.
  3. Melakukan `git push origin main`.
- **GitHub Actions Auto-Deploy**: Begitu di-push ke branch `main`, GitHub Actions akan otomatis mentransfer file ke Hostinger `public_html` via FTP.

## 2. ATURAN KRUSIAL HOSTINGER FTP (ANTI-NESTED PUBLIC_HTML)
- Akun FTP Hostinger sudah **mendarat langsung di dalam `public_html`** saat login.
- Nilai `server-dir` di file `.github/workflows/deploy.yml` **HARUS SELALU BERISI `./`**.
- **JANGAN PERNAH** mengubah `server-dir` menjadi `public_html/`, karena akan membuat folder anakan `public_html` di dalam `public_html`.

## 3. STRUKTUR DAN ARSITEKTUR APLIKASI
- **Backend**: Native PHP 8.x + MySQL PDO (`config.php`, `api/orders.php`, `api/driver.php`, `api/admin.php`, `api/chat.php`).
- **Frontend**: HTML5, Vanilla CSS, Vanilla JavaScript. File web root berada langsung di root (`index.html`, `trip.html`, `driver.html`, `admin.html`).
- **Peta & Tracking**: Wajib 100% Open Source menggunakan **Leaflet.js + OpenStreetMap (OSM / CartoDB)**. Dilarang menggunakan Google Maps API berbayar.
- **Anti-Pembajakan Penumpang (Disintermediation)**:
  - Nomor telepon pelanggan disensor di PWA sopir (`0819-****-4321`).
  - Koordinasi perjalanan dan penjemputan dilakukan via In-App Chat.
  - Nomor telepon asli hanya tersimpan di database untuk CRM & verifikasi Admin.
