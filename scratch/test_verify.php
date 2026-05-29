<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\WebScraping;
use Illuminate\Http\Request;

// Log in as user ID 3 (whose plan expired on 2026-05-18)
$user = User::find(3);
auth()->login($user);

echo "Logged in as User: {$user->email}\n";
echo "Will Expire: {$user->will_expire}\n";

// Let's create a temporary WebScraping record for user 3
$record = WebScraping::create([
    'uuid' => (string) Str::uuid(),
    'user_id' => $user->id,
    'title' => 'Test apotek',
    'type' => 'google_maps_no_api',
    'category_id' => 117,
    'parameters' => [
        'city' => 'Jakarta',
        'state' => 'DKI Jakarta',
        'country' => 'Indonesia'
    ],
    'module' => 'whatsapp-web',
    'status' => 'pending'
]);

echo "Created temporary WebScraping record UUID: {$record->uuid}\n";

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
    echo "Response content: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
} finally {
    // Delete the temporary record
    $record->delete();
    echo "Cleaned up temporary record.\n";
}
