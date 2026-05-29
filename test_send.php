<?php

use Modules\WhatsappWeb\App\Services\WhatsAppWebService;
use App\Models\Platform;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = new WhatsAppWebService();
$platform = Platform::where('id', 2)->first(); // jeff 6282354506569
$targetNumber = '6282261567685';
$jid = $service->setJid($targetNumber);

$message = [
    'text' => 'Halo ini adalah pesan test Bulk/Campaign dari JeffSender. Jika Anda menerima pesan ini, artinya sistem sudah normal.'
];

echo "Sending message to $targetNumber...\n";
$res = $service->sendMessage($platform->uuid, $jid, $message, 'text');

if ($res->successful()) {
    echo "Message sent successfully!\n";
    print_r($res->json());
} else {
    echo "Failed to send message.\n";
    print_r($res->json());
}
