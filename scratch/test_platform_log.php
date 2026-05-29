<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Platform;

$platform = Platform::first();

try {
    $log = $platform->logs()->create([
        'module' => 'whatsapp-web',
        'owner_id' => $platform->owner_id,
        'direction' => 'in',
        'message_type' => 'text',
        'message_text' => 'test message',
        'meta' => ['test' => 'data'],
    ]);
    echo "Successfully created log with ID: " . $log->id . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
