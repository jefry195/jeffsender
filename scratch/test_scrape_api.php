<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\WebScraping;
use Illuminate\Http\Request;

// Login as user ID 2
$user = User::find(2);
auth()->login($user);

// Let's find one WebScraping record for user 2
$record = WebScraping::where('user_id', 2)->first();
if (!$record) {
    echo "No WebScraping record found for user 2\n";
    exit;
}

echo "Testing with record UUID: {$record->uuid}\n";

// Simulate a POST request to api/webscraping/v1/scrape/{record}/fetch
$request = Request::create(
    "api/webscraping/v1/scrape/{$record->uuid}/fetch",
    'POST',
    [], // parameters
    [], // cookies
    [], // files
    $_SERVER // server variables
);

try {
    $response = Route::dispatch($request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content: " . substr($response->getContent(), 0, 500) . "...\n";
} catch (\Exception $e) {
    echo "Exception occurred: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
