<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Modules\WhatsappWeb\App\Models\WhatsappWebApp::create([
    'platform_id' => 2,
    'name' => 'Google Sheet Data Laporan',
    'site_link' => 'https://docs.google.com/spreadsheets/d/1EYQlGK3-PKr6GNGcyM1jyEckFka3RMD2ITcHgEpDRrA/edit?gid=0#gid=0',
    'user_id' => 1,
    'key' => \Illuminate\Support\Str::random(10)
]);

echo "App Successfully Added to DB!\n";
