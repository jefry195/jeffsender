<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\WhatsappWeb\App\Jobs\HandleIncomingMessageJob;
use App\Models\Platform;

$platform = Platform::first();
$payload = [
    'sessionId' => $platform->uuid,
    'event' => 'MESSAGES_UPSERT',
    'data' => [
        'messages' => [
            [
                'key' => [
                    'remoteJid' => '082354506569@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'ABCD1234EFGH'
                ],
                'message' => [
                    'conversation' => '/tutup'
                ]
            ]
        ]
    ]
];

echo "Dispatching job...\n";
HandleIncomingMessageJob::dispatchSync($payload);
echo "Job dispatched synchronously.\n";

$count = \App\Models\PlatformLog::where('owner_id', $platform->owner_id)->count();
echo "Total logs: " . $count . "\n";
