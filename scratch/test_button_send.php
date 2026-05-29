<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new Modules\WhatsappWeb\App\Services\WhatsAppWebService();
// Test with Buttons structure
$res = $service->apiClient()->post("/chats/send?id=382dc19f-d06b-4d8b-a132-9f359b31b2ea", [
    'receiver' => '6282171898184@s.whatsapp.net',
    'message' => [
        'text' => 'Halo! Pilih salah satu tombol di bawah ini:',
        'footer' => 'JeffSender Test',
        'buttons' => [
            ['buttonId' => 'id1', 'buttonText' => ['displayText' => 'Pilihan A'], 'type' => 1],
            ['buttonId' => 'id2', 'buttonText' => ['displayText' => 'Pilihan B'], 'type' => 1],
        ],
        'headerType' => 1
    ]
]);

dump($res->status());
dump($res->json());
