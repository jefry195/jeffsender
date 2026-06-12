<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WebScraping;

$record = WebScraping::find(12);
echo "Status    : " . $record->status . PHP_EOL;
echo "Data Count: " . $record->scraped_data()->count() . " item" . PHP_EOL;
echo "Jobs Queue: " . \DB::table('jobs')->count() . " jobs" . PHP_EOL;
