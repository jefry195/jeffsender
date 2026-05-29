<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- Web Scrapings Table ---\n";
$scrapings = DB::table('web_scrapings')->latest()->get();
foreach ($scrapings as $s) {
    echo "ID: {$s->id}, UUID: {$s->uuid}, User: {$s->user_id}, Title: {$s->title}, Type: {$s->type}, Status: {$s->status}, Query Count: {$s->query_count}\n";
}

echo "\n--- Web Scraped Data (latest 5) ---\n";
$data = DB::table('web_scraped_data')->latest()->take(5)->get();
foreach ($data as $d) {
    echo "ID: {$d->id}, Scraping ID: {$d->web_scraping_id}, Unique ID: {$d->unique_id}\n";
    echo "Data: " . substr($d->data, 0, 200) . "...\n";
}
