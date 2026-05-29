<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\WebScraping;
use Illuminate\Http\Request;

// Log in as user ID 2 (jefry.m95@gmail.com)
$user = User::find(2);
auth()->login($user);

echo "Logged in as User: {$user->email}\n";

$controller = new \Modules\WebScraping\App\Http\Controllers\WebScrapingController();
$request = Request::create(
    "user/web-scraping/scrape",
    'POST',
    [
        'title' => 'Test Restaurant',
        'type' => 'google_maps_no_api',
        'category_id' => 117,
        'parameters' => [
            'city' => 'Bontang',
            'state' => 'Kalimantan Timur',
            'country' => 'Indonesia'
        ]
    ]
);

try {
    $response = $controller->store($request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Target URL: " . $response->getTargetUrl() . "\n";
    
    // Find the created record and clean it up
    $record = WebScraping::where('title', 'Test Restaurant')->first();
    if ($record) {
        echo "Successfully created record UUID: {$record->uuid}\n";
        $record->delete();
        echo "Cleaned up test record.\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
