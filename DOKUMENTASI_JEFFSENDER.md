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
*Dibuat dengan penuh dedikasi oleh Antigravity AI khusus untuk Jefri - Dooren'z Percetakan.* 💠✨
