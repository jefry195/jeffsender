<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$r = App\Models\WebScraping::where('uuid', '2f749b61-01e0-4b9b-94a5-8d4557e58dc2')->first();
if ($r) {
    echo 'Type: ' . $r->type . PHP_EOL;
    echo 'Status: ' . $r->status . PHP_EOL;
    echo 'Data Count: ' . $r->scraped_data()->count() . PHP_EOL;
} else {
    echo "Record not found" . PHP_EOL;
}
