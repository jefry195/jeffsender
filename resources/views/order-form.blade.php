<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Order Percetakan & Sablon - Dooren'z</title>
    
    <!-- Tailwind CSS for modern responsive styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Inter Font from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .remove-item-btn {
            transition: all 0.2s;
        }
        .remove-item-btn:hover {
            transform: scale(1.05);
        }
        /* Loading Spinner */
        .loader {
            border: 3px solid #f3f3f3;
            border-radius: 50%;
            border-top: 3px solid #3498db;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            display: none;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4 md:p-8">

    <div class="max-w-7xl w-full mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- LEFT COLUMN: ORDER FORM -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl border border-gray-100 p-6 md:p-8">
            
            <!-- Header -->
            <div class="text-center mb-8 border-b border-gray-100 pb-6">
                <div class="inline-flex p-3 bg-green-50 rounded-2xl text-green-600 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <h1 class="text-3xl font-extrabold text-gray-900">Form Order Pelanggan</h1>
                <p class="text-gray-500 mt-1 font-medium">Dooren'z Percetakan & Sablon</p>
            </div>

            <!-- Main Form -->
            <form id="orderForm" name="submit-to-google-sheet" class="space-y-6">
                <!-- App key and Auth key passed from Laravel -->
                <input type="hidden" id="appKey" value="{{ $appKey }}">
                <input type="hidden" id="authKey" value="{{ $authKey }}">
                <input type="hidden" id="adminPhone" value="{{ $adminPhone }}">
                <input type="hidden" id="platformUuid" value="{{ $uuid }}">
                
                <!-- Compiled items details hidden input -->
                <input type="hidden" name="detail_pesanan" id="detailPesananHidden">

                <!-- Pemesan details section -->
                <div class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100 space-y-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <span class="w-1.5 h-5 bg-green-500 rounded-full"></span>
                        Detail Pemesan
                    </h2>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">No. Order</label>
                        <input type="text" id="orderNumberDisplay" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-100 text-gray-600 font-mono font-bold cursor-not-allowed" value="Mengenerate..." readonly>
                        <input type="hidden" name="no_order" id="orderNumberVal" value="AUTO_GENERATED"> 
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Nama Pemesan</label>
                            <input type="text" id="customerName" name="nama" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all" placeholder="Contoh: Budi Santoso" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Nomor WhatsApp</label>
                            <div class="flex rounded-xl shadow-sm">
                                <span class="inline-flex items-center px-4 border border-r-0 border-gray-200 bg-gray-50 text-gray-500 rounded-l-xl font-medium">
                                    +62
                                </span>
                                <input type="tel" id="customerWa" name="wa" class="w-full px-4 py-2.5 border border-gray-200 rounded-r-xl focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all" placeholder="81234567890" required>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tanggal Order</label>
                            <input type="text" id="orderDate" name="tgl_order" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-100 text-gray-600 cursor-not-allowed" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tanggal Deadline</label>
                            <input type="date" id="deadlineDate" name="deadline" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <div>
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-4">
                        <span class="w-1.5 h-5 bg-green-500 rounded-full"></span>
                        Item Pesanan
                    </h2>
                    
                    <div id="itemsContainer" class="space-y-4">
                        <!-- Items dynamically added here -->
                    </div>

                    <!-- Add Item Button -->
                    <button type="button" id="addItemBtn" class="w-full mt-4 bg-gray-50 text-gray-700 font-semibold py-3 px-4 rounded-xl border border-dashed border-gray-300 hover:bg-gray-100 hover:border-gray-400 focus:outline-none transition-all flex items-center justify-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                        </svg>
                        <span>Tambah Item Lain</span>
                    </button>
                </div>

                <div class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100 space-y-4">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Catatan Tambahan</label>
                    <textarea id="notes" name="catatan" rows="3" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all" placeholder="Jelaskan jika ada permintaan khusus. Misal: 'Sablon 1 warna hitam', 'Desain dikirim via email'"></textarea>
                </div>

                <!-- Submit Button / Loader -->
                <div class="mt-8 flex items-center gap-4">
                    <button type="submit" id="submitBtn" class="flex-1 bg-green-500 text-white font-bold py-3.5 px-6 rounded-xl hover:bg-green-600 focus:outline-none focus:ring-4 focus:ring-green-500/20 transition-all flex items-center justify-center space-x-2 shadow-lg shadow-green-500/15">
                        <svg id="waIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                            <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                        </svg>
                        <span id="btnText">Kirim Order & WhatsApp</span>
                    </button>
                    <div class="loader" id="btnLoader"></div>
                </div>
            </form>
        </div>

        <!-- RIGHT COLUMN: TRACKING & GUIDES -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Order Tracking -->
            <div id="cek-order" class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 border-l-4 border-indigo-500">
                <h3 class="text-xl font-bold text-gray-800 mb-3 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Lacak Pesanan
                </h3>
                <p class="text-gray-500 text-sm mb-4">
                    Masukkan No. Order untuk melihat status pengerjaan (Contoh: ORD-2026-1001).
                </p>
                <div class="flex gap-2">
                    <input type="text" id="searchOrderId" class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" placeholder="No. Order">
                    <button id="btnSearchOrder" class="bg-indigo-500 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-600 transition-all shadow-md shadow-indigo-500/10">Cek</button>
                </div>
                
                <!-- Search Result -->
                <div id="searchResult" class="mt-4 hidden p-4 bg-gray-50 rounded-xl text-sm border border-gray-100">
                    <div id="resultContent" class="space-y-2">
                        <!-- Search data loaded here -->
                    </div>
                </div>
            </div>

            <!-- About Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 border-l-4 border-green-500">
                <h3 class="text-xl font-bold text-gray-800 mb-3 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Tentang Aplikasi
                </h3>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Formulir ini mempermudah proses pemesanan percetakan & sablon. Pesanan Anda akan otomatis tersimpan dalam database dan memicu notifikasi WhatsApp instan ke nomor Anda.
                </p>
            </div>

            <!-- Guide Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 border-l-4 border-blue-500">
                <h3 class="text-xl font-bold text-gray-800 mb-3 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Cara Mengisi Form
                </h3>
                <ol class="list-decimal list-inside text-gray-500 text-sm space-y-2.5">
                    <li>Isi <b>Nama</b> dan <b>WhatsApp</b> aktif Anda.</li>
                    <li>Pilih <b>Produk</b> yang ingin dipesan.</li>
                    <li>Lengkapi spesifikasi (Ukuran, Bahan, Warna, dll) pada form yang muncul.</li>
                    <li>Klik <b>"+ Tambah Item Lain"</b> jika memesan lebih dari satu jenis produk.</li>
                    <li>Tekan tombol <b>"Kirim Order"</b> untuk memproses pemesanan Anda.</li>
                </ol>
            </div>

            <!-- Features Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 border-l-4 border-purple-500">
                <h3 class="text-xl font-bold text-gray-800 mb-3 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Fitur Otomatis
                </h3>
                <ul class="text-gray-500 text-sm space-y-2.5">
                    <li class="flex items-start">
                        <span class="mr-2">✅</span>
                        <span><b>No. Order Unik:</b> Dibuat otomatis saat memuat halaman (ORD-Tahun-Random).</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">✅</span>
                        <span><b>Auto WA:</b> Gateway notifikasi instan langsung mengirim pesan dari dashboard.</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">✅</span>
                        <span><b>Real-time Sheet:</b> Data langsung terkirim ke Google Sheets administrasi.</span>
                    </li>
                </ul>
            </div>
            
             <!-- Notes Card -->
             <div class="bg-amber-50 rounded-2xl p-6 border border-amber-100">
                <h3 class="text-md font-bold text-amber-800 mb-2 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Catatan Penting
                </h3>
                <p class="text-amber-700 text-xs leading-relaxed">
                    Setelah pesanan dikirim, harap menunggu konfirmasi desain dalam 1-2 hari. Pesanan yang sudah masuk tahap produksi (ACC) tidak dapat diubah kembali.
                </p>
            </div>

        </div>

    </div>

    <script>
        // ==========================================
        // SCRIPT URL GOOGLE SHEET
        // ==========================================
        const scriptURL = 'https://script.google.com/macros/s/AKfycbwHT7_vgasVww5lr2wmxGmv03vh3ibRDunqLnAeqTfOmdyagaf4E5o4PQtO-XF_bYm4/exec'; 
        // ==========================================

        document.addEventListener('DOMContentLoaded', function() {
            const itemsContainer = document.getElementById('itemsContainer');
            const addItemBtn = document.getElementById('addItemBtn');
            const orderForm = document.getElementById('orderForm');
            const orderDateInput = document.getElementById('orderDate');
            const orderNumberDisplay = document.getElementById('orderNumberDisplay');
            const orderNumberVal = document.getElementById('orderNumberVal');
            const submitBtn = document.getElementById('submitBtn');
            const btnLoader = document.getElementById('btnLoader');
            const btnText = document.getElementById('btnText');
            const customerWaInput = document.getElementById('customerWa');
            
            // Search Elements
            const btnSearchOrder = document.getElementById('btnSearchOrder');
            const searchOrderId = document.getElementById('searchOrderId');
            const searchResult = document.getElementById('searchResult');
            const resultContent = document.getElementById('resultContent');

            let itemCounter = 0;

            // Generate auto order number
            const today = new Date();
            const year = today.getFullYear();
            const uniqueOrderNo = "{{ $nextOrderNo }}";
            orderNumberDisplay.value = uniqueOrderNo;
            orderNumberVal.value = uniqueOrderNo;

            // Set current order date
            const day = String(today.getDate()).padStart(2, '0');
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const dateString = `${day}-${month}-${year}`;
            orderDateInput.value = dateString;

            // Load saved customer name and WA from localStorage if available
            const savedName = localStorage.getItem('customerName');
            const savedWa = localStorage.getItem('customerWa');
            if (savedName) {
                document.getElementById('customerName').value = savedName;
            }
            if (savedWa) {
                customerWaInput.value = savedWa;
            }

            // Format WhatsApp Number (clean non-digits and strip starting 0 or 62)
            customerWaInput.addEventListener('input', function(e) {
                let cleanValue = this.value.replace(/\D/g, '');
                if (cleanValue.startsWith('62')) cleanValue = cleanValue.substring(2);
                else if (cleanValue.startsWith('0')) cleanValue = cleanValue.substring(1);
                this.value = cleanValue;
            });

            // TRACK ORDER LOGIC
            btnSearchOrder.addEventListener('click', function() {
                const orderId = searchOrderId.value.trim();
                if (!orderId) {
                    alert("Masukkan No. Order terlebih dahulu");
                    return;
                }

                const originalText = btnSearchOrder.textContent;
                btnSearchOrder.textContent = "...";
                btnSearchOrder.disabled = true;

                let searchId = orderId;
                // If pure digits (e.g. "212"), convert to ORD-Year-Number format
                if (/^\d+$/.test(searchId)) {
                    const num = parseInt(searchId, 10);
                    const year = num <= 8 ? 2025 : today.getFullYear();
                    searchId = `ORD-${year}-${num}`;
                }
                // If it is ORD-Number format (e.g. "ORD-212"), convert to ORD-Year-Number
                else if (/^ORD-\d+$/i.test(searchId)) {
                    const num = parseInt(searchId.replace(/ORD-/i, ''), 10);
                    const year = num <= 8 ? 2025 : today.getFullYear();
                    searchId = `ORD-${year}-${num}`;
                }
                
                const searchUrl = `${scriptURL}?action=search&no_order=${encodeURIComponent(searchId)}`;

                fetch(searchUrl)
                    .then(response => response.json())
                    .then(data => {
                        searchResult.classList.remove('hidden');
                        if (data.result === 'found') {
                            const d = data.data;
                            resultContent.innerHTML = `
                                <div class="border-b border-gray-200 pb-2 mb-2">
                                    <p class="font-bold text-gray-800 text-base">${d.nama}</p>
                                    <p class="text-xs text-gray-400">Order: ${d.no_order}</p>
                                    <p class="text-xs text-gray-400">Tgl Order: ${d.tgl_order}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <p class="text-gray-400 mb-1">Status Desain</p>
                                        <span class="px-2 py-0.5 rounded-full font-semibold ${getStatusColor(d.status_desain)}">${d.status_desain || 'Pending'}</span>
                                    </div>
                                    <div>
                                        <p class="text-gray-400 mb-1">Status Cetak</p>
                                        <span class="px-2 py-0.5 rounded-full font-semibold ${getStatusColor(d.status_cetak)}">${d.status_cetak || 'Pending'}</span>
                                    </div>
                                    <div>
                                        <p class="text-gray-400 mb-1">Status Finishing</p>
                                        <span class="px-2 py-0.5 rounded-full font-semibold ${getStatusColor(d.status_finishing)}">${d.status_finishing || 'Pending'}</span>
                                    </div>
                                    <div>
                                        <p class="text-gray-400 mb-1">Ekspedisi</p>
                                        <p class="font-semibold text-gray-750">${d.ekspedisi || '-'}</p>
                                    </div>
                                     <div class="col-span-2 border-t border-gray-200 pt-2 mt-1">
                                        <div class="flex justify-between">
                                            <div>
                                                <p class="text-gray-400">Selesai Produksi</p>
                                                <p class="font-bold text-gray-700">${d.tgl_selesai || '-'}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-gray-400">Tgl Kirim</p>
                                                <p class="font-bold text-gray-700">${d.tgl_kirim || '-'}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            resultContent.innerHTML = `<p class="text-red-500 text-center py-2">Data tidak ditemukan.<br>Pastikan No. Order benar.</p>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("Gagal mengambil data. Coba lagi nanti.");
                    })
                    .finally(() => {
                        btnSearchOrder.textContent = originalText;
                        btnSearchOrder.disabled = false;
                    });
            });

            function getStatusColor(status) {
                if (!status) return "text-gray-500 bg-gray-55";
                const s = status.toLowerCase();
                if (s.includes('selesai') || s.includes('acc') || s.includes('siap') || s.includes('lunas')) return "text-green-700 bg-green-50";
                if (s.includes('proses')) return "text-blue-700 bg-blue-50";
                if (s.includes('revisi') || s.includes('hold') || s.includes('belum')) return "text-red-700 bg-red-50";
                if (s.includes('menunggu')) return "text-yellow-700 bg-yellow-50";
                return "text-gray-700 bg-gray-50";
            }

            // Products list dropdown generator
            function createProductSelect() {
                const select = document.createElement('select');
                select.className = 'product-select w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all text-sm text-gray-700';
                
                const options = {
                    'APPAREL': ['KAOS CUSTOM', 'JERSEY CUSTOM'],
                    'KEMASAN': [
                        'DUS / BOX CUSTOM', 
                        'LUNCHBOX', 
                        'RICEBOX', 
                        'KEMASAN PABRIKAN'
                    ],
                    'GELAS PLASTIK - UKURAN 10 OZ': ['BSM 10 OZ OVAL'],
                    'GELAS PLASTIK - UKURAN 12 OZ': [
                        '12 OZ DATAR 5 GR',
                        '12 OZ DATAR 7 GR SAP',
                        '12 OZ DATAR 7 GR STARINDO',
                        '12 OZ OVAL 8 GR',
                        '12 OZ DATAR PET + TUTUP STRAWLESS',
                        '12 OZ OVAL CUP INJECTION + TUTUP',
                        '12 OZ OVAL INJECT FROSTED + TUTUP',
                        '12 OZ DATAR INJECTION + TUTUP',
                        '12 OZ DATAR INJECT FROSTED + TUTUP'
                    ],
                    'GELAS PLASTIK - UKURAN 14 OZ': [
                        '14 OZ DATAR 5 GR',
                        '14 OZ DATAR 7 GR',
                        '14 OZ OVAL SAP',
                        '14 OZ OVAL BSM',
                        '14 OZ DATAR PET + TUTUP STRAWLESS',
                        '14 OZ OVAL PET + TUTUP STRAWLESS',
                        '14 OZ INJECT + TUTUP',
                        '14 OZ INJECT FROSTED + TUTUP',
                        '14 OZ INJECT HITAM GLOSSY (NO MERK) + TUTUP',
                        '14 OZ INJECT HITAM FROSTED (VICTORY) + TUTUP',
                        '14 OZ INJECT DOFF MERAH + TUTUP'
                    ],
                    'GELAS PLASTIK - UKURAN 16 OZ': [
                        '16 OZ DATAR 5 GR',
                        '16 OZ DATAR 7 GR SAP',
                        '16 OZ DATAR 7 GR STARINDO',
                        '16 OZ OVAL SAP',
                        '16 OZ OVAL SJP',
                        '16 OZ DATAR PET + TUTUP DATAR PET',
                        '16 OZ DATAR CUP INJECTION + TUTUP',
                        '16 OZ INJECT HITAM GLOSSY (NO MERK) + TUTUP'
                    ],
                    'GELAS PLASTIK - UKURAN 18 OZ': [
                        '18 OZ DATAR SJP',
                        '18 OZ OVAL SJP',
                        '18 OZ OVAL / DATAR BSM'
                    ],
                    'GELAS PLASTIK - UKURAN 22 OZ': [
                        '22 OZ DATAR GSD 8 GR',
                        '22 OZ DATAR SAP 10 GR',
                        '22 OZ DATAR SJP',
                        '22 OZ OVAL SJP',
                        '22 OZ STARCUP'
                    ],
                    'GELAS PLASTIK - UKURAN 24 OZ': [
                        '24 OZ INJECT + TUTUP'
                    ],
                    'PAPERCUP': [
                        'PAPERCUP 8 OZ PUTIH + TUTUP',
                        'PAPERCUP 8 OZ HITAM TINTA PUTIH + TUTUP',
                        'PAPERCUP 8 OZ HITAM TINTA EMAS + TUTUP',
                        'PAPERCUP 8 OZ DOUBLE WALL + TUTUP',
                        'PAPERCUP 9 OZ PUTIH + TUTUP',
                        'PAPERCUP 12 OZ DOUBLE WALL KRAFT + TUTUP',
                        'PAPERCUP 12 OZ PUTIH TEBAL + TUTUP HITAM',
                        'PAPERCUP 12 OZ PUTIH TIPIS BSM + TUTUP'
                    ],
                    'PAPERBOWL': [
                        'PAPERBOWL 500 ML DK + TUTUP', 
                        'PAPERBOWL 650 ML DK + TUTUP', 
                        'PAPERBOWL 800 ML DK + TUTUP'
                    ],
                    'PLASTIK OPP': ['PLASTIK OPP 12X25', 'PLASTIK OPP 15X25', 'PLASTIK OPP 15X15'],
                    'PLASTIK KRESEK BENING': [
                        'PLASTIK KRESEK BENING UKURAN 15',
                        'PLASTIK KRESEK BENING UKURAN 20',
                        'PLASTIK KRESEK BENING UKURAN 24',
                        'PLASTIK KRESEK BENING UKURAN 25',
                        'PLASTIK KRESEK BENING UKURAN 28',
                        'PLASTIK KRESEK BENING UKURAN 30',
                        'PLASTIK KRESEK BENING UKURAN 32',
                        'PLASTIK KRESEK BENING UKURAN 35'
                    ],
                    'PLASTIK KRESEK PUTIH': [
                        'PLASTIK KRESEK PUTIH UKURAN 15',
                        'PLASTIK KRESEK PUTIH UKURAN 20',
                        'PLASTIK KRESEK PUTIH UKURAN 24',
                        'PLASTIK KRESEK PUTIH UKURAN 25',
                        'PLASTIK KRESEK PUTIH UKURAN 28',
                        'PLASTIK KRESEK PUTIH UKURAN 30',
                        'PLASTIK KRESEK PUTIH UKURAN 32',
                        'PLASTIK KRESEK PUTIH UKURAN 35'
                    ],
                    'KERTAS KEMASAN': ['KERTAS NASI / BURGER (30 X 30 CM)', 'KERTAS NASI / WRAP PAPER'],
                    'ALAS KAKI KERTAS (SAMSON)': ['PLANO BAGI 4 (45 X 60 CM)', 'PLANO BAGI 5 (36 X 54 CM)', 'PLANO BAGI 6 (45 X 40 CM)'],
                    'SOUVENIR': [
                        'GELAS KACA', 'TUMBLER', 'TAS SPUNBOND', 'PAYUNG', 'JAM DINDING', 'MUG', 'BALON', 'LAINNYA'
                    ],
                    'PAPERBAG': ['PAPERBAG PUTIH', 'PAPERBAG COKLAT', 'PAPERBAG CUSTOM FULL COLOR'],
                    'TUTUP (LID)': [
                        'TUTUP DATAR PET', 'TUTUP CEMBUNG PET', 'TUTUP STRAWLESS PET', 
                        'TUTUP DATAR PP', 'TUTUP CEMBUNG PP', 'TUTUP STRAWLESS PP', 'CUP SEALER'
                    ],
                    'PRODUK CETAK LAINNYA': ['SPANDUK / BANNER', 'STIKER', 'KARTU NAMA', 'BROSUR', 'NOTA / KWITANSI', 'KALENDER', 'UNDANGAN', 'MENU', 'KERTAS SOUVENIR', 'KERTAS CUSTOM']
                };

                select.add(new Option('--- Pilih Produk ---', ''));

                for (const groupLabel in options) {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = groupLabel;
                    options[groupLabel].forEach(item => {
                        const option = new Option(item, `${groupLabel}|${item}`);
                        optgroup.appendChild(option);
                    });
                    select.appendChild(optgroup);
                }
                return select;
            }

            // Function to add a new item block
            function addNewItem() {
                itemCounter++;
                const itemDiv = document.createElement('div');
                itemDiv.className = 'item-block bg-gray-50/50 border border-gray-100 p-5 rounded-2xl space-y-4 relative transition-all';
                
                const productSelect = createProductSelect();
                
                itemDiv.innerHTML = `
                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                        <label class="block text-sm font-bold text-gray-800">Item #${itemCounter}</label>
                        ${itemCounter > 1 ? '<button type="button" class="remove-item-btn text-red-500 hover:text-red-700 text-xs font-semibold flex items-center gap-1">&times; Hapus Item</button>' : ''}
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Produk / Barang</label>
                        <div class="select-placeholder"></div>
                    </div>
                    
                    <!-- Kaos Fields -->
                    <div class="kaos-fields hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Ukuran</label>
                            <select class="kaos-size-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option value="S">S (48x66 cm)</option>
                                <option value="M">M (50x68 cm)</option>
                                <option value="L">L (52x70 cm)</option>
                                <option value="XL">XL (54x72 cm)</option>
                                <option value="2XL">2XL (56x74 cm)</option>
                                <option value="3XL">3XL (58x76 cm)</option>
                                <option value="4XL">4XL (60x78 cm)</option>
                                <option value="5XL">5XL (62x80 cm)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Bahan</label>
                            <select class="kaos-material-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option>Drifit Milano</option>
                                <option>Drifit Benzema</option>
                                <option>Cotton Combed 30s</option>
                                <option>Cotton Combed 24s</option>
                                <option>Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Model</label>
                            <select class="kaos-model-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option>Lengan Pendek</option>
                                <option>Lengan Panjang</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Warna Kain</label>
                            <input type="text" class="kaos-color-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="Hitam, Navy, Merah">
                        </div>
                    </div>

                    <!-- Custom Box Form -->
                    <div class="custom-box-type-field hidden space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Jenis Bentuk Box</label>
                            <select class="custom-box-type-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option value="Kotak Standar">Kotak Standar</option>
                                <option value="Tray (Tanpa Tutup)">Tray (Tanpa Tutup)</option>
                                <option value="Lunchbox (Tutup Sambung)">Lunchbox (Tutup Sambung)</option>
                                <option value="Box Burger">Box Burger</option>
                                <option value="Box Kebab">Box Kebab</option>
                                <option value="Pisah Tutup (Base & Lid)">Pisah Tutup (Base & Lid terpisah)</option>
                                <option value="Lainnya">Lainnya (Jelaskan di Catatan)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dimension & Materials -->
                    <div class="dimension-fields hidden space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Ukuran Kemasan (P x L x T cm)</label>
                            <div class="grid grid-cols-3 gap-2">
                                <input type="number" step="0.1" class="panjang-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="P">
                                <input type="number" step="0.1" class="lebar-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="L">
                                <input type="number" step="0.1" class="tinggi-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="T">
                            </div>
                        </div>
                        <div class="dynamic-material-field hidden">
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Bahan Kertas</label>
                            <select class="custom-box-material-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option value="">-- Pilih Bahan --</option>
                                <option>Ivory 210 gsm</option>
                                <option>Ivory 230 gsm</option>
                                <option>Ivory 250 gsm</option>
                                <option>Ivory 300 gsm</option>
                                <option>Ivory 350 gsm</option>
                                <option>Duplex 250 gsm</option>
                                <option>Duplex 310 gsm</option>
                                <option>Duplex 350 gsm</option>
                                <option>Kraft 275 gsm</option>
                                <option>Kraft 310 gsm</option>
                                <option>Kraft 350 gsm</option>
                                <option>Art Carton 210 gsm</option>
                                <option>Art Carton 260 gsm</option>
                                <option>Art Carton 310 gsm</option>
                                <option>Lainnya (Tulis di Catatan)</option>
                            </select>
                        </div>
                        
                        <div class="custom-box-finishing-field hidden space-y-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">Laminasi</label>
                                <select class="custom-box-laminasi-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                    <option>Tanpa Laminasi</option>
                                    <option>Laminasi Doff (Luar)</option>
                                    <option>Laminasi Glossy (Luar)</option>
                                    <option>Laminasi Doff (Dalam & Luar)</option>
                                    <option>Laminasi Glossy (Dalam & Luar)</option>
                                    <option>Waterbase / Varnish UV</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">Finishing Tambahan</label>
                                <div class="grid grid-cols-2 gap-2 bg-white p-3 rounded-xl border border-gray-200">
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="custom-box-finishing-checkbox h-4 w-4 text-green-600 focus:ring-green-500 border-gray-200 rounded" value="Poli (Hot Foil)">
                                        <span class="text-xs text-gray-700">Poli (Hot Foil)</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="custom-box-finishing-checkbox h-4 w-4 text-green-600 focus:ring-green-500 border-gray-200 rounded" value="Emboss (Timbul)">
                                        <span class="text-xs text-gray-700">Emboss</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="custom-box-finishing-checkbox h-4 w-4 text-green-600 focus:ring-green-500 border-gray-200 rounded" value="Spot UV">
                                        <span class="text-xs text-gray-700">Spot UV</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="custom-box-finishing-checkbox h-4 w-4 text-green-600 focus:ring-green-500 border-gray-200 rounded" value="Pond (Die Cut)">
                                        <span class="text-xs text-gray-700">Pond (Die Cut)</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="custom-box-finishing-checkbox h-4 w-4 text-green-600 focus:ring-green-500 border-gray-200 rounded" value="Jendela Mika">
                                        <span class="text-xs text-gray-700">Jendela Mika</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="custom-box-finishing-checkbox h-4 w-4 text-green-600 focus:ring-green-500 border-gray-200 rounded" value="Lem & Lipat">
                                        <span class="text-xs text-gray-700">Lem & Lipat</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lunchbox Fields -->
                    <div class="lunchbox-fields hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Ukuran Lunchbox</label>
                            <select class="lunchbox-size-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option>S (11x11x5 cm)</option>
                                <option>M (18x10,5x5 cm)</option>
                                <option>L (20x12,5x5 cm)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Bahan</label>
                            <select class="lunchbox-material-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option>Ivory 250gsm</option>
                                <option>Kraft 290gsm</option>
                            </select>
                        </div>
                    </div>

                    <!-- Ricebox Fields -->
                    <div class="ricebox-fields hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Ukuran Ricebox</label>
                            <select class="ricebox-size-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option>S</option>
                                <option>M</option>
                                <option>L</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Bahan</label>
                            <select class="ricebox-material-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option>Ivory 250gsm</option>
                                <option>Kraft 290gsm</option>
                            </select>
                        </div>
                    </div>

                    <!-- Pabrikan Box Fields -->
                    <div class="kemasan-pabrikan-fields hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Ukuran</label>
                            <select class="kemasan-pabrikan-size-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option>R3 (14x10,5x6,5 cm)</option>
                                <option>R5 (16x12x7 cm)</option>
                                <option>NX (22x14x6,5 cm)</option>
                                <option>R8 (17,5x17,5x6,8 cm)</option>
                                <option>R10 (19,5x19,5x6,8 cm)</option>
                                <option>R11 (21,5x21,5x6,8 cm)</option>
                                <option>18x16x7 cm</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Bahan</label>
                            <select class="kemasan-pabrikan-material-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option>Ivory</option>
                                <option>Kraft</option>
                                <option>Duplex</option>
                            </select>
                        </div>
                    </div>

                    <!-- Cup Sealer -->
                    <div class="cup-sealer-fields hidden space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Jenis Tinta</label>
                            <select class="cup-sealer-ink-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option>Tinta Logo Sedikit</option>
                                <option>Tinta Full Block</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tumbler -->
                    <div class="tumbler-fields hidden space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Kode Item Tumbler</label>
                            <input type="text" class="tumbler-code-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="Contoh: TM-001">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Kemasan Tambahan</label>
                             <select class="tumbler-addon-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option value="-">Tanpa Tambahan</option>
                                <option value="Tile">Tile</option>
                                <option value="Kotak">Kotak</option>
                                <option value="Mika">Mika</option>
                                <option value="Lainnya">Lainnya (Tulis di Catatan)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Kertas Cetak Custom -->
                    <div class="spesifikasi-kertas-fields hidden space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">Ukuran Custom (cm)</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <input type="number" step="0.1" class="spesifikasi-panjang-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="P">
                                    <input type="number" step="0.1" class="spesifikasi-lebar-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="L">
                                    <input type="number" step="0.1" class="spesifikasi-tinggi-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="T">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">Bahan Kertas</label>
                                <input type="text" class="bahan-kertas-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="Art Paper 150gsm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">Cetak Sisi</label>
                                <select class="cetak-sisi-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                    <option>1 Sisi</option>
                                    <option>2 Sisi (Bolak-balik)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">Laminasi</label>
                                <select class="laminasi-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                    <option>Tanpa Laminasi</option>
                                    <option>Glossy 1 Sisi</option>
                                    <option>Doff 1 Sisi</option>
                                    <option>Glossy 2 Sisi</option>
                                    <option>Doff 2 Sisi</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Finishing</label>
                            <input type="text" class="finishing-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="Potong jadi, Lipat, dll">
                        </div>
                    </div>

                    <!-- Quantity, Tinta, & Desain -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Jumlah</label>
                            <input type="text" class="quantity-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="Contoh: 1000 pcs" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Tinta</label>
                            <input type="text" class="color-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" placeholder="Contoh: HIJAU TUA">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-500 mb-1">Desain</label>
                            <select class="desain-select w-full px-3 py-2 border border-gray-200 rounded-xl text-sm bg-white">
                                <option value="Desain Baru (Perlu dibuatkan)">Desain Baru (Perlu dibuatkan)</option>
                                <option value="Desain Lama (Sudah pernah order)">Desain Lama (Sudah pernah order)</option>
                            </select>
                        </div>
                    </div>
                `;
                
                // Append select dropdown into placeholder
                itemDiv.querySelector('.select-placeholder').appendChild(productSelect);
                itemsContainer.appendChild(itemDiv);
                
                // Show/hide sub-fields on product selection
                productSelect.addEventListener('change', function() {
                    const val = this.value;
                    const parts = val.split('|');
                    const group = parts[0];
                    const item = parts[1] || '';
                    
                    // Hide all fields first
                    const fields = [
                        itemDiv.querySelector('.kaos-fields'),
                        itemDiv.querySelector('.custom-box-type-field'),
                        itemDiv.querySelector('.dimension-fields'),
                        itemDiv.querySelector('.dynamic-material-field'),
                        itemDiv.querySelector('.custom-box-finishing-field'),
                        itemDiv.querySelector('.lunchbox-fields'),
                        itemDiv.querySelector('.ricebox-fields'),
                        itemDiv.querySelector('.kemasan-pabrikan-fields'),
                        itemDiv.querySelector('.cup-sealer-fields'),
                        itemDiv.querySelector('.tumbler-fields'),
                        itemDiv.querySelector('.spesifikasi-kertas-fields')
                    ];
                    fields.forEach(f => { if(f) f.classList.add('hidden'); });
                    
                    // Show corresponding fields
                    if (group === 'APPAREL') {
                        itemDiv.querySelector('.kaos-fields').classList.remove('hidden');
                    } else if (item === 'DUS / BOX CUSTOM') {
                        itemDiv.querySelector('.custom-box-type-field').classList.remove('hidden');
                        itemDiv.querySelector('.dimension-fields').classList.remove('hidden');
                        itemDiv.querySelector('.dynamic-material-field').classList.remove('hidden');
                        itemDiv.querySelector('.custom-box-finishing-field').classList.remove('hidden');
                    } else if (item === 'LUNCHBOX') {
                        itemDiv.querySelector('.lunchbox-fields').classList.remove('hidden');
                    } else if (item === 'RICEBOX') {
                        itemDiv.querySelector('.ricebox-fields').classList.remove('hidden');
                    } else if (item === 'KEMASAN PABRIKAN') {
                        itemDiv.querySelector('.kemasan-pabrikan-fields').classList.remove('hidden');
                    } else if (item === 'CUP SEALER') {
                        itemDiv.querySelector('.cup-sealer-fields').classList.remove('hidden');
                    } else if (group === 'SOUVENIR' && item === 'TUMBLER') {
                        itemDiv.querySelector('.tumbler-fields').classList.remove('hidden');
                    } else if (group === 'PRODUK CETAK LAINNYA' || group === 'PAPERBAG' || group === 'KERTAS KEMASAN') {
                        itemDiv.querySelector('.spesifikasi-kertas-fields').classList.remove('hidden');
                    }
                });

                // Remove item logic
                const removeBtn = itemDiv.querySelector('.remove-item-btn');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function() {
                        itemDiv.remove();
                        reindexItems();
                    });
                }
            }

            // Reindex item block numbers after removing one
            function reindexItems() {
                itemCounter = 0;
                document.querySelectorAll('.item-block').forEach((block) => {
                    itemCounter++;
                    block.querySelector('label.text-gray-800').textContent = `Item #${itemCounter}`;
                });
            }

            // Create first item block
            addNewItem();

            // Click listener for add item
            addItemBtn.addEventListener('click', addNewItem);

            // FORM SUBMISSION LOGIC
            orderForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const customerName = document.getElementById('customerName').value.trim();
                const customerWa = customerWaInput.value.trim();
                const orderNo = orderNumberVal.value;
                const deadline = document.getElementById('deadlineDate').value;
                const notes = document.getElementById('notes').value.trim();
                const appKey = document.getElementById('appKey').value;
                const authKey = document.getElementById('authKey').value;

                if (!customerName || !customerWa) {
                    alert("Nama dan nomor WhatsApp wajib diisi!");
                    return;
                }

                // Save to localStorage immediately on submit so repeat orders don't require typing name/phone again
                localStorage.setItem('customerName', customerName);
                localStorage.setItem('customerWa', customerWa);

                // Compile all items details into a readable list
                const itemBlocks = document.querySelectorAll('.item-block');
                if (itemBlocks.length === 0) {
                    alert("Tambahkan minimal satu item pesanan!");
                    return;
                }

                let itemsSummaryText = '';
                let itemsSummaryHtml = '';

                itemBlocks.forEach((block, idx) => {
                    const productSelect = block.querySelector('.product-select');
                    if (!productSelect || !productSelect.value) return;
                    
                    const [group, productName] = productSelect.value.split('|');
                    const qty = block.querySelector('.quantity-input')?.value || '';
                    const color = block.querySelector('.color-input')?.value || '';
                    const desain = block.querySelector('.desain-select')?.value || 'Desain Baru (Perlu dibuatkan)';
                    
                    let details = [];
                    
                    if (group === 'APPAREL') {
                        const size = block.querySelector('.kaos-size-select')?.value;
                        const mat = block.querySelector('.kaos-material-select')?.value;
                        const model = block.querySelector('.kaos-model-select')?.value;
                        const kainColor = block.querySelector('.kaos-color-input')?.value;
                        details.push(`Ukuran: ${size}`, `Bahan: ${mat}`, `Model: ${model}`, `Warna: ${kainColor}`);
                    } else if (productName === 'DUS / BOX CUSTOM') {
                        const type = block.querySelector('.custom-box-type-select')?.value;
                        const p = block.querySelector('.panjang-input')?.value;
                        const l = block.querySelector('.lebar-input')?.value;
                        const t = block.querySelector('.tinggi-input')?.value;
                        const mat = block.querySelector('.custom-box-material-select')?.value;
                        const lam = block.querySelector('.custom-box-laminasi-select')?.value;
                        
                        let finish = [];
                        block.querySelectorAll('.custom-box-finishing-checkbox:checked').forEach(cb => finish.push(cb.value));
                        
                        details.push(`Bentuk: ${type}`, `Dimensi: ${p}x${l}x${t} cm`, `Bahan: ${mat}`, `Laminasi: ${lam}`);
                        if (finish.length > 0) details.push(`Finishing: ${finish.join(', ')}`);
                    } else if (productName === 'LUNCHBOX') {
                        const size = block.querySelector('.lunchbox-size-select')?.value;
                        const mat = block.querySelector('.lunchbox-material-select')?.value;
                        details.push(`Ukuran: ${size}`, `Bahan: ${mat}`);
                    } else if (productName === 'RICEBOX') {
                        const size = block.querySelector('.ricebox-size-select')?.value;
                        const mat = block.querySelector('.ricebox-material-select')?.value;
                        details.push(`Ukuran: ${size}`, `Bahan: ${mat}`);
                    } else if (productName === 'KEMASAN PABRIKAN') {
                        const size = block.querySelector('.kemasan-pabrikan-size-select')?.value;
                        const mat = block.querySelector('.kemasan-pabrikan-material-select')?.value;
                        details.push(`Ukuran: ${size}`, `Bahan: ${mat}`);
                    } else if (productName === 'CUP SEALER') {
                        const ink = block.querySelector('.cup-sealer-ink-select')?.value;
                        details.push(`Tinta: ${ink}`);
                    } else if (group === 'SOUVENIR' && productName === 'TUMBLER') {
                        const code = block.querySelector('.tumbler-code-input')?.value;
                        const addon = block.querySelector('.tumbler-addon-select')?.value;
                        details.push(`Kode: ${code}`, `Kemasan: ${addon}`);
                    } else if (group === 'PRODUK CETAK LAINNYA' || group === 'PAPERBAG' || group === 'KERTAS KEMASAN') {
                        const p = block.querySelector('.spesifikasi-panjang-input')?.value;
                        const l = block.querySelector('.spesifikasi-lebar-input')?.value;
                        const t = block.querySelector('.spesifikasi-tinggi-input')?.value;
                        const mat = block.querySelector('.bahan-kertas-input')?.value;
                        const sisi = block.querySelector('.cetak-sisi-select')?.value;
                        const lam = block.querySelector('.laminasi-select')?.value;
                        const finish = block.querySelector('.finishing-input')?.value;
                        
                        details.push(`Dimensi: ${p}x${l}x${t} cm`, `Bahan: ${mat}`, `Sisi: ${sisi}`, `Laminasi: ${lam}`);
                        if (finish) details.push(`Finishing: ${finish}`);
                    }
                    
                    const detailsStr = details.join(', ');
                    const specDetails = detailsStr ? ` (${detailsStr})` : '';
                    
                    if (idx > 0) {
                        itemsSummaryText += '\n';
                    }
                    itemsSummaryText += `*${idx + 1}. ${productName}*\n` +
                                       `   - Kategori: ${group}\n` +
                                       `   - Jumlah: ${qty}\n` +
                                       `   - Tinta: ${color || '-'}\n`;
                    if (specDetails) {
                        itemsSummaryText += `   - Spesifikasi: ${specDetails}\n`;
                    }
                    itemsSummaryText += `   - Desain: ${desain}\n`;
                });

                itemsSummaryHtml = itemsSummaryText;

                // Validate if order details are empty to prevent empty logs
                if (!itemsSummaryText.trim()) {
                    alert("Rincian pesanan kosong! Silakan pilih produk terlebih dahulu.");
                    resetFormState();
                    return;
                }

                // Put the compiled summary into the hidden field for Google Sheet
                document.getElementById('detailPesananHidden').value = itemsSummaryHtml;

                // UI loading state
                btnLoader.style.display = 'inline-block';
                submitBtn.disabled = true;
                btnText.textContent = "Sedang Mengirim...";

                const adminPhone = document.getElementById('adminPhone').value || '6282261567685';
                const platformUuid = document.getElementById('platformUuid')?.value || '';
                const fetchUrl = platformUuid ? `/order-number/next/${platformUuid}` : '/order-number/next';

                const helperPrepareFormData = (orderNoVal) => {
                    const formData = new FormData(orderForm);
                    formData.set('no_order', orderNoVal);
                    
                    // Add variations of keys to guarantee compatibility with Google Sheets Apps Script
                    formData.set('items', itemsSummaryHtml);
                    formData.set('Detail Item', itemsSummaryHtml);
                    formData.set('detail_item', itemsSummaryHtml);
                    formData.set('detailitem', itemsSummaryHtml);
                    formData.set('detail_pesanan', itemsSummaryHtml);
                    
                    formData.set('Catatan', notes);
                    formData.set('catatan', notes);
                    
                    formData.set('No. Order', orderNoVal);
                    formData.set('noorder', orderNoVal);
                    
                    formData.set('Nama Pemesan', customerName);
                    formData.set('nama', customerName);
                    formData.set('namapemesan', customerName);
                    
                    formData.set('No. WhatsApp', '62' + customerWa);
                    formData.set('nowhatsapp', '62' + customerWa);
                    formData.set('wa', customerWa);
                    
                    formData.set('Tgl. Order', orderDateInput.value);
                    formData.set('tgl_order', orderDateInput.value);
                    formData.set('tglorder', orderDateInput.value);
                    
                    const formattedDeadline = deadline ? deadline.split('-').reverse().join('-') : '-';
                    formData.set('Deadline', formattedDeadline);
                    formData.set('deadline', deadline);
                    
                    return new URLSearchParams(formData);
                };

                // Fetch latest order number dynamically right before submit
                fetch(fetchUrl)
                    .then(response => {
                        if (!response.ok) throw new Error("Gagal mengambil nomor order terbaru");
                        return response.json();
                    })
                    .then(data => {
                        const updatedOrderNo = data.nextOrderNo || orderNumberVal.value;
                        
                        // Update values
                        orderNumberDisplay.value = updatedOrderNo;
                        orderNumberVal.value = updatedOrderNo;

                        const urlEncodedData = helperPrepareFormData(updatedOrderNo);

                        // Post to Google Sheets
                        return fetch(scriptURL, { method: 'POST', body: urlEncodedData })
                            .then(response => {
                                console.log('Google Sheets success!', response);
                                
                                // Send silent background confirmation from admin to customer if keys exist
                                if (appKey && authKey) {
                                    sendBackgroundWaSilent(customerWa, customerName, updatedOrderNo, appKey, authKey);
                                }
                                
                                // Redirect customer directly to admin's WhatsApp chat
                                redirectToAdminWa(customerWa, customerName, updatedOrderNo, itemsSummaryText, deadline, notes, adminPhone);
                            });
                    })
                    .catch(error => {
                        console.error('Error in order submission flow:', error);
                        // Fallback: submit with current order number if AJAX fails
                        const currentOrderNo = orderNumberVal.value;
                        const urlEncodedData = helperPrepareFormData(currentOrderNo);
                        
                        fetch(scriptURL, { method: 'POST', body: urlEncodedData })
                            .then(response => {
                                if (appKey && authKey) {
                                    sendBackgroundWaSilent(customerWa, customerName, currentOrderNo, appKey, authKey);
                                }
                                redirectToAdminWa(customerWa, customerName, currentOrderNo, itemsSummaryText, deadline, notes, adminPhone);
                            })
                            .catch(err => {
                                alert("Gagal terhubung ke database sheet, mengalihkan langsung ke WhatsApp Admin...");
                                redirectToAdminWa(customerWa, customerName, currentOrderNo, itemsSummaryText, deadline, notes, adminPhone);
                            });
                    });
            });

            // Send silent background confirmation WhatsApp from Admin to Customer
            function sendBackgroundWaSilent(customerWa, customerName, orderNo, appKey, authKey) {
                const waMessage = `*DOOREN'Z ORDER RECEIVED* 💠\n\nHalo *${customerName}*,\nTerima kasih telah melakukan pemesanan. Pesanan Anda telah tersimpan di sistem kami dengan No. Order *${orderNo}*.\n\n_Admin kami akan memvalidasi pesanan Anda segera. Harap simpan No. Order untuk melakukan pelacakan status._ 🙏`;

                fetch('/send-message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'app_key': appKey,
                        'auth_key': authKey,
                        'to': '62' + customerWa,
                        'message': waMessage,
                        'type': 'text'
                    })
                });
            }

            // Redirect customer directly to admin's WhatsApp chat with prefilled order format
            function redirectToAdminWa(customerWa, customerName, orderNo, itemsSummaryText, deadline, notes, adminNumber) {
                const formattedDeadline = deadline ? deadline.split('-').reverse().join('-') : '-';
                
                const waMessage = `*===== PESANAN BARU =====*\n\n` +
                    `*No. Order:* ${orderNo}\n` +
                    `*Nama Pemesan:* ${customerName}\n` +
                    `*No. WhatsApp:* +62${customerWa}\n\n` +
                    `*Tgl. Order:* ${orderDateInput.value}\n` +
                    `*Tgl. Deadline:* ${formattedDeadline}\n\n` +
                    `-----------------------------------\n` +
                    `*DETAIL PESANAN:*\n` +
                    `${itemsSummaryText}\n` +
                    `-----------------------------------\n\n` +
                    `*Catatan Tambahan:*\n` +
                    `${notes || '-'}\n\n` +
                    `*Penting:* Desain akan dikonfirmasi dalam 1–2 hari setelah dikirim (kecuali hari Minggu/tanggal merah). Setelah *FIX*, pesanan langsung diproses dan desain *tidak bisa diubah kembali*.\n\n` +
                    `_Untuk pesanan dengan desain baru, mohon kirimkan file logo Anda (jika ada)._\n\n` +
                    `Terima kasih`;
                
                // Reset form and states before redirecting
                orderForm.reset();
                resetFormState();
                
                // Redirect directly in current window to trigger WhatsApp native app deep-link reliably on mobile
                window.location.href = `https://wa.me/${adminNumber}?text=${encodeURIComponent(waMessage)}`;
            }

            function resetFormState() {
                btnLoader.style.display = 'none';
                submitBtn.disabled = false;
                btnText.textContent = "Kirim Order & WhatsApp";
            }
        });
    </script>
</body>
</html>
