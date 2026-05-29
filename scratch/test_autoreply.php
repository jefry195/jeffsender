<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\WhatsappWeb\App\Jobs\HandleIncomingMessageJob;

$payload = [
    'sessionId' => '382dc19f-d06b-4d8b-a132-9f359b31b2ea',
    'event' => 'messages.upsert',
    'data' => [
        'messages' => [
            [
                'key' => [
                    'remoteJid' => '622180675787@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'TEST_MESSAGE_ID_' . time(),
                ],
                'message' => [
                    'conversation' => 'halo'
                ],
                'pushName' => 'Tester'
            ]
        ]
    ]
];

HandleIncomingMessageJob::dispatch($payload);
echo "Job dispatched successfully\n";
