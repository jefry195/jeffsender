# 📜 DOKUMENTASI & PANDUAN LENGKAP (DOOREN'Z PERCETAKAN / JEFFSENDER)

Dokumen ini berisi panduan instalasi, otomatisasi sistem, serta solusi teknis untuk semua fitur yang telah dikembangkan untuk WhatsML.

---

## 🚀 1. CARA MENJALANKAN APLIKASI (LOCAL)
Gunakan file batch ini untuk menyalakan semua mesin utama dalam **satu klik**:
1.  **File Utama:** `D:\App\jalankan_whatsml.bat`. 
2.  **Layanan yang Otomatis Berjalan di Background (via PM2):**
    *   **Apache & MySQL (XAMPP):** Berjalan sebagai Windows Services otomatis.
    *   **whatsapp-server:** Port 3000 (Penghubung WhatsApp).
    *   **laravel-server:** Port 8010 (Server inti web admin).
    *   **laravel-queue:** Robot penarik antrean pesan otomatis.
    *   **laravel-sheets-tracker:** Robot sinkronisasi Google Sheets.
    *   **laravel-vite:** Server desain/frontend aset hot reload.
3.  **Diagnosis Otomatis:** Saat file `.bat` dijalankan, ia akan otomatis memanggil perintah `artisan jeffsender:self-heal` untuk memeriksa status PM2, mengaktifkan auto-reply platform, mendeteksi antrean error, serta memverifikasi status koneksi sesi WhatsApp ke server Node.js.

---

## 🛠️ 2. SOLUSI ERROR KRUSIAL (DIARY PERBAIKAN)

### ❌ Error: MySQL Berhenti Sendiri (Stop/Crash)
*   **Penyebab:** Tabel sistem `db` di database `mysql` korup/rusak.
*   **Solusi:** Jalankan perintah `aria_chk -r` pada file database yang rusak di folder `xampp\mysql\data`.

### ❌ Error: Robot Tidak Membalas (Pencocokan Gagal / Disconnect)
*   **Penyebab A (Pusher):** Error notifikasi dashboard menghentikan proses balasan.
    *   **Solusi:** Kode di `WebhookHandlerService.php` sudah dibungkus *try-catch* agar robot tetap membalas meskipun dashboard error.
*   **Penyebab B (HP Format):** Pesan dari HP (Extended Text) tidak terbaca.
    *   **Solusi:** Penambahan deteksi `extendedTextMessage` di file `HandleIncomingMessageJob.php`.
*   Penyebab C (ID @lid): WhatsApp format baru (`@lid`) gagal mengirim balasan dari server.
    *   Solusi: Server Node.js sebelumnya memformat JID `@lid` secara salah menjadi `@s.whatsapp.net` dan melakukan validasi `isExists` (yang selalu gagal untuk LID). Masalah ini telah diperbaiki di [chatsController.js](file:///c:/xampp/htdocs/jeffsender/whatsapp-server/controllers/chatsController.js) dengan mengizinkan Liaison ID (`@lid`) lolos tanpa re-formatting dan mem-bypass pengecekan `isExists`.
*   Penyebab D (AutoReply Null): Fitur AutoReply default tidak mengirim pesan.
    *   Solusi: Perbaikan kolom database dari `message` menjadi `message_template` pada file `app/Services/AutoReplyService.php`.
*   Penyebab E (Sesi WA Stuck/Silent Disconnect): Koneksi ke whatsapp-server di PM2 berstatus *online* tetapi Baileys terputus secara senyap.
    *   Solusi: Menambahkan check koneksi HTTP di perintah `jeffsender:self-heal` ke endpoint `/sessions/status/{uuid}`. Jika koneksi terdeteksi offline atau tidak autentik, sistem akan otomatis melakukan `pm2 restart whatsapp-server` untuk memaksa koneksi ulang. Opsi perbaikan otomatis ini juga langsung dieksekusi setiap kali komputer dinyalakan melalui folder Startup.

---

## 🕒 2b. PENGATURAN ZONA WAKTU (WITA / MAKASSAR)
Untuk memastikan sistem pencocokan waktu operasional toko berjalan akurat dan tidak mengirimkan pesan penutupan di luar jam kerja secara tidak menentu:
1.  **File `.env`:** Parameter `TIME_ZONE=Asia/Makassar` telah ditambahkan. Ini memastikan Laravel memproses semua log, penjadwalan, dan tanggal di zona waktu WITA (UTC+8).
2.  **Penting - Cache Konfigurasi:** Jika Anda mengubah zona waktu di `.env`, Anda wajib menjalankan `php artisan config:clear` agar konfigurasi baru dimuat oleh Laravel (terutama oleh queue worker yang berjalan terus-menerus di PM2).
3.  **Logic Program:** Server menggunakan `now()->timezone('Asia/Makassar')` secara real-time saat membandingkan waktu saat ini dengan jam operasional toko:
    *   **Senin - Jumat:** 09.00 - 18.00 WITA.
    *   **Sabtu:** 09.00 - 17.00 WITA.
    *   **Minggu / Libur Nasional:** Tutup otomatis.

---

## 🖥️ 2c. OTOMATISASI DAN PERBAIKAN SAAT BOOTING PC (STARTUP)
Agar aplikasi dapat langsung berjalan di background dan melakukan perbaikan mandiri saat komputer pertama kali dinyalakan:
1.  **Lokasi File Startup:** Script `jeffsender_startup.bat` ditempatkan di folder Startup Windows:
    `C:\Users\User\AppData\Roaming\Microsoft\Windows\Start Menu\Programs\Startup\jeffsender_startup.bat`
2.  **Fungsi Script:** Setiap kali komputer menyala dan pengguna login, script ini akan secara otomatis:
    *   Memeriksa apakah **Apache** (XAMPP) sudah berjalan, dan menyalakannya jika mati.
    *   Memeriksa apakah **MySQL** (XAMPP) sudah berjalan, dan menyalakannya jika mati.
    *   Memulai seluruh layanan WhatsML (Node.js whatsapp-server, Laravel server, Queue worker, Vite, Tracker) menggunakan PM2 (`pm2 start ecosystem.config.cjs`).
    *   Menjalankan perintah diagnosis mandiri `php artisan jeffsender:self-heal` untuk memperbaiki error database, queue jobs yang macet, atau sesi WhatsApp yang terputus secara senyap.
3.  **Hasil:** Anda tidak perlu melakukan konfigurasi manual setiap pagi. Cukup nyalakan komputer, biarkan sistem berjalan di background secara otomatis.

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

## 🖥️ 9. PANDUAN SERVER LOW-WATT (MINI PC & CASAOS)

Panduan ini ditujukan untuk memindahkan server lokal dari PC Desktop utama ke perangkat Mini PC hemat daya (Low Watt) agar dapat menyala nonstop 24 jam dengan biaya listrik minimal.

### A. Rekomendasi Hardware
*   **Processor**: Intel N100 (4 Cores, 4 Threads, TDP 6 Watt).
*   **RAM**: 16 GB DDR4/DDR5 (Sangat direkomendasikan dibanding 8GB agar aman menjalankan MySQL + NodeJS WhatsApp Server + Laravel + OS).
*   **Penyimpanan**: SSD M.2 NVMe 256GB / 512GB (Wajib NVMe demi kecepatan I/O database).
*   **Contoh Unit**: GMKtec NucBox G3, Beelink S12 Pro, atau MSI Cubi N ADL.

### B. Kalkulasi Biaya Listrik Bulanan (Meteran 6.600 VA / Tarif Rp1.699,53 per kWh)
*   **PC Desktop Lama (Ryzen 5 4600G + GTX 1650)**:
    *   Daya: ~65 - 100 Watt.
    *   Biaya Listrik Bulanan: **Rp79.500 s/d Rp122.500**.
*   **Mini PC Rekomendasi (Intel N100)**:
    *   Daya: ~6 - 12 Watt.
    *   Biaya Listrik Bulanan: **Rp7.100 s/d Rp14.250** (Hemat Daya 85% - 90%).

### C. Cara Instalasi CasaOS (Ubuntu Server Host)
1.  Buat USB Bootable menggunakan file ISO **Ubuntu Server 22.04 LTS** lewat aplikasi Rufus di Windows.
2.  Colok flashdisk ke Mini PC, masuk ke BIOS (tekan `Del` atau `F7` berulang kali saat booting), lalu atur agar boot ke USB.
3.  Lakukan instalasi Ubuntu Server ke dalam penyimpanan disk SSD (Windows bawaan akan terhapus).
4.  Setelah masuk ke terminal Ubuntu Server, jalankan instalasi CasaOS otomatis dengan perintah satu baris ini:
    ```bash
    curl -fsSL https://get.casaos.io | sudo bash
    ```
5.  Setelah selesai, akses dashboard CasaOS melalui web browser dari komputer lain di jaringan lokal yang sama dengan mengetik alamat IP Mini PC tersebut (contoh: `http://192.168.1.100`).

### D. Konfigurasi IP Static
Untuk mempermudah akses remote dan koneksi server, atur IP Static pada Mini PC Anda:

#### Cara Utama (Static DHCP Reservation di Router)
1.  Buka admin dashboard Router Anda (misal `192.168.1.1`).
2.  Masuk ke menu **DHCP Server** ➔ **Address Reservation** / **Static Lease**.
3.  Daftarkan **MAC Address** Mini PC Anda dan tentukan IP static yang Anda inginkan (misal `192.168.1.100`).

#### Cara Alternatif (Netplan di Ubuntu Server Host)
1.  Cek nama interface jaringan Anda dengan mengetik `ip a` (misal: `enp1s0`).
2.  Buka file Netplan: `sudo nano /etc/netplan/01-netcfg.yaml`
3.  Gunakan konfigurasi berikut (sesuaikan spasi dan nama interface):
    ```yaml
    network:
      version: 2
      renderer: networkd
      ethernets:
        enp1s0:
          dhcp4: no
          addresses:
            - 192.168.1.100/24
          routes:
            - to: default
              via: 192.168.1.1
          nameservers:
            addresses:
              - 8.8.8.8
              - 1.1.1.1
    ```
4.  Terapkan pengaturan dengan perintah: `sudo netplan apply`

---

## 🤖 10. PEMBATASAN PESAN LUAR JAM OPERASIONAL (RATE LIMITING)
*   **Penyebab Error / Masalah:** Saat berada di luar jam operasional (malam hari/hari Minggu), setiap kali ada pesan masuk yang tidak memiliki kata kunci pencocokan otomatis (AutoReply) atau tidak berada di tengah percakapan flow (seperti kalkulator), sistem akan membalas dengan pesan jam operasional tutup. Namun, jika pelanggan terus mengirim chat beruntun, robot juga membalasnya berulang kali secara terus-menerus sehingga terdeteksi sebagai spam.
*   **Solusi & Perbaikan:** 
    1. Ditambahkan mekanisme pembatasan frekuensi pengiriman (Rate Limiting) berbasis Cache (`\Cache`) selama 24 jam per percakapan/chat.
    2. Kode pengecekan diletakkan di dalam method `handleOutOfHoursReply()` pada file [AutoReplyService.php](file:///c:/xampp/htdocs/jeffsender/app/Services/AutoReplyService.php) dan modul Whatsapp-Web [AutoReplyService.php](file:///c:/xampp/htdocs/jeffsender/modules/WhatsappWeb/App/Services/AutoReplyService.php).
    3. Menggunakan Cache Key unik `ooo_sent_{platform_id}_{conversation_id}` dan `ooo_sent_web_{platform_id}_{chat_id}` dengan masa aktif 24 jam (`now()->addDay()`).
    4. **Penting:** Fitur AutoReply utama (berbasis kecocokan keyword) dan conversational flow (seperti kalkulator box / digital printing) **TIDAK terganggu** dan tetap akan merespon secara real-time meskipun pesan di luar jam operasional sudah pernah terkirim sebelumnya.
    5. **Aktivasi Perubahan:** Dikarenakan queue worker berjalan terus-menerus di memori PM2, proses antrean wajib di-restart agar perubahan kode baru ini aktif:
       ```bash
       pm2 restart laravel-queue
       ```

---

## 📊 11. OTOMATISASI SINKRONISASI PRICELIST GOOGLE SHEET (DAILY SYNC)
*   **Masalah Caching & Sub-Template:** Saat mengunduh data pricelist dari Google Sheets, Google cenderung menyajikan versi ekspor lama yang ter-cache. Selain itu, chatbot memerlukan pembaruan pada 10 sub-template ukuran cup/bag detail (seperti `Pricelist: Gelas Plastik 1W - 12oz`, `18oz`, `Papercup`, dll.) agar harga baru ter-update secara menyeluruh.
*   **Solusi & Pengembangan:**
    1. **Bypass Cache**: Kode penarik data dalam [GoogleSheetsPricelistService.php](file:///c:/xampp/htdocs/jeffsender/app/Services/GoogleSheetsPricelistService.php) diperbarui agar menyertakan parameter acak waktu (`t=time()`) pada export URL, mematikan fungsi caching Google Sheets secara paksa.
    2. **Pemecahan Sub-Template Ukuran**: Ditambahkan fungsi parser otomatis `updateSubTemplates()` yang memecah baris kategori `GP 1 WARNA` berdasarkan ukuran cup/bag, lalu meng-upsert 10 sub-template detail secara real-time.
    3. **Otomatisasi Harian via PM2**: Agar proses sinkronisasi ini berjalan otomatis setiap hari secara mandiri, ditambahkan aplikasi scheduler pada file [ecosystem.config.cjs](file:///c:/xampp/htdocs/jeffsender/ecosystem.config.cjs) dengan nama `laravel-scheduler` yang menjalankan daemon scheduler bawaan Laravel (`php artisan schedule:work`).
    4. **Jadwal Eksekusi**: Perintah `sheets:update-pricelist` dijadwalkan berjalan secara otomatis **setiap 1 hari sekali (daily)** di background.

---
*Dibuat dengan penuh dedikasi oleh Antigravity AI khusus untuk Jefri - Dooren'z Percetakan.* 💠✨
