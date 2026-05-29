<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new Modules\WhatsappWeb\App\Services\WhatsAppWebService();
$res = $service->sendMessage(
    '382dc19f-d06b-4d8b-a132-9f359b31b2ea', 
    '6282171898184@s.whatsapp.net', 
    [
        'text' => 'Halo! Ini adalah contoh List Message dari sistem. Silakan klik tombol "Lihat Menu" di bawah untuk mencoba:',
        'footer' => 'JeffSender Automated Test',
        'title' => 'Layanan Percobaan',
        'button_text' => 'Lihat Menu',
        'sections' => [
            [
                'title' => 'Pilih Salah Satu:',
                'rows' => [
                    ['title' => 'Cek Saldo', 'description' => 'Klik ini untuk mencoba auto-reply saldo', 'rowId' => 'row_1'],
                    ['title' => 'Hubungi Admin', 'description' => 'Klik ini untuk mencoba auto-reply admin', 'rowId' => 'row_2']
                ]
            ]
        ]
    ], 
    'list'
);

dump($res->status());
dump($res->json());
