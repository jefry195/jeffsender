<?php

use App\Models\WebScraping;
use App\Models\WebScrapedData;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$record = WebScraping::latest()->first();
echo "Record UUID: " . $record->uuid . "\n";
echo "Record Type: " . $record->type . "\n";
echo "Record Status: " . $record->status . "\n";
echo "Scraped Data Count: " . $record->scraped_data()->count() . "\n";

foreach ($record->scraped_data as $data) {
    echo " - " . ($data->data['name'] ?? 'Unknown') . " (" . ($data->data['phone_number'] ?? 'No Phone') . ")\n";
}
