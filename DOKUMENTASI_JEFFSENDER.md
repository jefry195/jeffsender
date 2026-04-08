# 📜 DOKUMENTASI & PANDUAN LENGKAP (DOOREN'Z PERCETAKAN / JEFFSENDER)

Dokumen ini berisi panduan instalasi, otomatisasi sistem, serta solusi teknis untuk semua fitur yang telah dikembangkan untuk WhatsML.

---

## 🚀 1. CARA MENJALANKAN APLIKASI (LOCAL)
Gunakan file batch ini untuk menyalakan semua mesin utama dalam **satu klik**:
1.  **File Utama:** `D:\App\jalankan_whatsml.bat`. 
2.  **Layanan yang Otomatis Berjalan:**
    *   **Apache & MySQL:** Server web dan database (XAMPP).
    *   **Laravel:** Mesin utama aplikasi (Port 8000).
    *   **Vite:** Server desain/frontend (Port 5173).
    *   **WhatsApp Server:** Penghubung ke WA (Port 3000).
    *   **Queue Runner:** Robot penarik antrean pesan (WAJIB agar balasan otomatis jalan).

---

## 🛠️ 2. SOLUSI ERROR KRUSIAL (DIARY PERBAIKAN)

### ❌ Error: MySQL Berhenti Sendiri (Stop/Crash)
*   **Penyebab:** Tabel sistem `db` di database `mysql` korup/rusak.
*   **Solusi:** Jalankan perintah `aria_chk -r` pada file database yang rusak di folder `xampp\mysql\data`.

### ❌ Error: Robot Tidak Membalas (Pencocokan Gagal)
*   **Penyebab A (Pusher):** Error notifikasi dashboard menghentikan proses balasan.
    *   **Solusi:** Kode di `WebhookHandlerService.php` sudah dibungkus *try-catch* agar robot tetap membalas meskipun dashboard error.
*   **Penyebab B (HP Format):** Pesan dari HP (Extended Text) tidak terbaca.
    *   **Solusi:** Penambahan deteksi `extendedTextMessage` di file `HandleIncomingMessageJob.php`.
*   **Penyebab C (ID @lid):** WhatsApp format baru (`@lid`) diblokir sistem.
    *   **Solusi:** Blokir dicopot di `HandleAutoReplyJob.php`.
*   **Penyebab D (AutoReply Null):** Fitur AutoReply default tidak mengirim pesan.
    *   **Solusi:** Perbaikan kolom database dari `message` menjadi `message_template` pada file `app/Services/AutoReplyService.php`.

---

## 📊 3. INTEGRASI GOOGLE SHEETS (PRICELIST)
Anda bisa membuat robot WA menjawab harga langsung dari Google Sheets.
1.  **Modul:** `Modules/GoogleSheets`.
2.  **Cara Kerja:** Laravel mengirim pesan pelanggan ke **URL Apps Script Web App**.
3.  **Template Sheet:**
    *   Kolom A: Nama Barang (Keyword).
    *   Kolom B: Harga (Jawaban).
4.  **Aktivasi:** Pastikan URL Apps Script sudah didaftarkan di meta platform database.

---

## 💰 4. INTEGRASI ULTIMATEPOS (CEK TAGIHAN)
Robot WA Anda sekarang bisa melihat data nota pelanggan di sistem POS (**jeff-pos**).
1.  **Nama Database POS:** `ultimatepos_v6`.
2.  **Logic:** Robot mencari nomor WA pelanggan di tabel `contacts` UltimatePOS, lalu menjumlahkan transaksi status `Due` (Belum Lunas).
3.  **Pesan Otomatis:** Robot mengirim rincian: *"Halo [Nama], total tagihan Anda adalah Rp [Jumlah]"*.

---

## 🔢 5. PERBAIKAN TAMPILAN & PAGINASI (CUSTOMERS)
Menu **Customers** pada modul **Whatsapp-Web** dan **Whatsapp** telah diperbarui untuk kenyamanan manajemen kontak:
1.  **Default 25 Baris:** Daftar kontak sekarang otomatis menampilkan **25 kontak** per halaman (sebelumnya 10).
2.  **Opsi Baris Baru:** Opsi "10 baris" telah dihapus karena terlalu sedikit. Sekarang tersedia opsi: **25, 50, 100, 500, dan Semua**.
3.  **Fix Bug 500 Kontak:** Masalah di mana memilih opsi "500" tetap menampilkan 10 kontak telah diperbaiki dengan memindahkan fungsi *router* ke posisi yang benar pada kode frontend.
4.  **Sinkronisasi Server:** Pengaturan jumlah baris di sisi server (backend) kini selaras dengan pilihan di layar (frontend).

---

## 🤖 6. MIGRASI AUTO REPLY (WHATSAPP WEB)
Sistem balasan otomatis untuk akun **jefry.m95@gmail.com** telah dipindahkan ke modul default:
1.  **Migrasi QA -> Auto:** 15 data dari modul `QA Reply` berhasil dipindahkan ke modul `Auto Reply` bawaan.
2.  **Target Device:** Device `6282261567685` kini menggunakan `auto_reply_method: default`.
3.  **Kelebihan:** Menggunakan modul default lebih stabil dan mendukung integrasi langsung dengan aplikasi inti tanpa bergantung pada modul tambahan dataset.

---

## 🤖 7. INTEGRASI AI (ARTIFICIAL INTELLIGENCE)
Robot Anda mendukung balasan cerdas menggunakan modul **Ai Training** (saat ini mendukung OpenAI).
1.  **Cara Kerja:** AI akan menjawab berdasarkan data "Dataset" yang Anda latih sendiri di menu **Ai Training**.
2.  **Langkah Aktivasi:**
    *   Masukkan API Key OpenAI di menu **Settings/Credentials**.
    *   Klik menu **Ai Training**, buat **Dataset**, dan masukkan data tanya-jawab.
    *   Jalankan **Fine-Tuning** (Tombol Latih).
    *   Pada menu **WhatsApp Web** > **Platforms**, ubah **Auto Reply Method** menjadi **Ai Training** dan pilih dataset Anda.
3.  **Kelebihan:** Robot bisa menjawab pertanyaan yang lebih kompleks dan natural, tidak terpaku pada kata kunci yang kaku.

---

## 📂 8. LOKASI FILE PENTING & KONFIGURASI
*   **File Startup:** `D:\App\jalankan_whatsml.bat`
*   **File Shutdown:** `D:\App\berhentikan_whatsml.bat`
*   **Koneksi Database:** `C:\xampp\htdocs\jeffsender\config\database.php` (Lihat bagian `'ultimatepos'`).
*   **Log WhatsApp:** `C:\xampp\htdocs\jeffsender\whatsapp-server\logs\app.log`
*   **Log Laravel:** `C:\xampp\htdocs\jeffsender\storage\logs\laravel.log`

---
*Dibuat dengan penuh dedikasi oleh Antigravity AI khusus untuk Jefri - Dooren'z Percetakan.* 💠✨
