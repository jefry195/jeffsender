# 🚀 PANDUAN MIGRASI APLIKASI (KE SHARED HOSTING / PC KANTOR)

Dokumen ini menjelaskan langkah-langkah memindahkan aplikasi JeffSender/WhatsML dari lingkungan lokal (saat ini) ke server yang lebih stabil (PC Kantor atau Hosting).

---

## 💻 1. OPSI A: PINDAH KE PC KANTOR (Windows)
Ini adalah opsi paling direkomendasikan karena Anda tetap bisa menggunakan XAMPP dan Windows yang familiar.

### Persiapan:
1.  **Instal XAMPP**: Gunakan PHP versi 8.2 ke atas.
2.  **Instal Node.js**: Download di [nodejs.org](https://nodejs.org/). Wajib untuk menjalankan WhatsApp Server (Port 3000).
3.  **Instal Git**: Untuk manajemen file (opsional tapi disarankan).

### Langkah Migrasi:
1.  **Copy Folder**: Salin seluruh folder `C:\xampp\htdocs\jeffsender` ke PC baru.
2.  **Export & Import Database**:
    - Backup database `whatsml` melalui phpMyAdmin di PC lama.
    - Import ke phpMyAdmin di PC baru.
3.  **Update Config (.env)**:
    - Buka file `.env` di folder root.
    - Update `DB_USERNAME`, `DB_PASSWORD`, dan `APP_URL` sesuai settingan di PC baru.
4.  **Otomatisasi**:
    - Siapkan file `.bat` seperti `jalankan_whatsml.bat` di PC baru.
    - Pastikan path (lokasi folder) di dalam file `.bat` diarahkan ke folder yang benar di PC baru.

---

## 🌐 2. OPSI B: PINDAH KE SHARED HOSTING (cPanel)
**PENTING:** Memindahkan aplikasi ini ke Shared Hosting memiliki keterbatasan teknis yang besar:
- **WhatsApp Server (Port 3000)**: Kebanyakan Shared Hosting memblokir port kustom.
- **Node.js**: Seringkali tidak bisa menjalankan Node.js secara background secara permanen.
- **Saran:** Gunakan **VPS (Virtual Private Server)** seperti DigitalOcean, Linode, atau Vultr seharga ~$6/bulan agar robot bisa jalan 24 jam tanpa macet.

### Langkah Jika Tetap Pakai Shared Hosting:
1.  **Upload File**: Zip folder `jeffsender` (kecuali folder `node_modules` dan `vendor`), lalu upload ke cPanel.
2.  **Extract**: Taruh di folder `public_html` (atau folder sub-domain).
3.  **Database**: Buat database baru di MySQL Wizard cPanel, lalu import database Anda.
4.  **PHP Version**: Pastikan versi PHP di cPanel diatur ke 8.2 atau 8.3.
5.  **WhatsApp Server**: Anda harus menjalankan WhatsApp Server di terminal cPanel (jika tersedia) menggunakan perintah:
    ```bash
    cd whatsapp-server && npm install && node app.js
    ```

---

## 🐧 3. OPSI C: PINDAH KE VPS (Ubuntu Server) - REKOMENDASI TERBAIK
Jika ingin sistem profesional, gunakan VPS Linux.

### Langkah Singkat:
1.  **Instal Stack**: Instal Nginx, MySQL, PHP 8.2, dan Node.js.
2.  **Git Clone**: Masukkan kode dari link repository Git Anda.
3.  **Instal Dependencies**: 
    - `composer install --no-dev`
    - `npm install && npm run build`
4.  **Supervisor**: Atur robot pengirim pesan (Queue) agar selalu jalan menggunakan **Supervisor**.
5.  **PM2**: Jalankan WhatsApp Server (Port 3000) menggunakan **PM2** agar jika server mati, robot otomatis hidup lagi.

---

## ⚠️ CATATAN KRUSIAL SETELAH PINDAH
1.  **PORT 3000**: Jika pindah server, pastikan Port 3000 dibuka (Firewall Allow) agar API WhatsApp bisa dipanggil oleh Laravel.
2.  **Queue Runner**: Robot balasan otomatis **TIDAK AKAN BEKERJA** jika perintah `php artisan queue:work` tidak dijalankan di background server baru.
3.  **Webhook**: Jika URL berubah, pastikan Anda merefresh koneksi WhatsApp Web dengan scan ulang jika diperlukan.

---
*Dibuat oleh Antigravity AI - Panduan Migrasi untuk Dooren'z Percetakan.* 💠🛠️
