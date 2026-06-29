<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WebScraping;
use Modules\WebScraping\App\Jobs\ScrapeJob;

echo "=== Jeffsender: Dispatch Pending Scrape Jobs ===" . PHP_EOL;
echo PHP_EOL;

/** @var \Illuminate\Database\Eloquent\Collection<WebScraping> $records */
$records = WebScraping::where('status', 'pending')->get();

if ($records->isEmpty()) {
    echo "Tidak ada record scraping dengan status 'pending'." . PHP_EOL;
    echo PHP_EOL;
    
    // Tampilkan semua record yang ada
    $all = WebScraping::orderBy('created_at', 'desc')->get(['id', 'title', 'type', 'status', 'uuid']);
    echo "Semua record web scraping yang ada:" . PHP_EOL;
    foreach ($all as $r) {
        echo "  ID: {$r->id} | {$r->title} | {$r->type} | Status: {$r->status}" . PHP_EOL;
    }
} else {
    echo "Ditemukan {$records->count()} record dengan status 'pending'." . PHP_EOL;
    echo "Mendispatch ke queue..." . PHP_EOL . PHP_EOL;

    /** @var WebScraping $record */
    foreach ($records as $record) {
        echo "  -> Dispatch: [{$record->id}] {$record->title} ({$record->type})" . PHP_EOL;
        // Reset ke in_progress dan hapus data lama jika ada
        $record->update(['status' => 'in_progress']);
        $record->scraped_data()->delete();
        ScrapeJob::dispatch($record);
    }

    echo PHP_EOL;
    echo "Selesai! Job sudah masuk queue." . PHP_EOL;
    echo "Pastikan queue worker berjalan (jalankan_queue.bat)" . PHP_EOL;
}
