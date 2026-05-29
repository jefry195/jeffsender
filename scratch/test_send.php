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
        'image' => 'C:\xampp\htdocs\jeffsender\public\uploads\26\04\1776415424MgcurVPll5u2oJnXKqjU.png', 
        'caption' => 'Test Antigravity'
    ], 
    'image'
);

dump($res->status());
dump($res->json());
