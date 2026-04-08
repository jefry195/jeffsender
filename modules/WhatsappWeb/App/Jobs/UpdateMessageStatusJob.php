<?php

namespace Modules\WhatsappWeb\App\Jobs;

use App\Models\CampaignLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateMessageStatusJob implements ShouldQueue
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $payload)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Handle both MESSAGES_UPDATE and MESSAGES_UPSERT payload structures
        // MESSAGES_UPDATE: data is an array of message updates
        // MESSAGES_UPSERT: data.messages is an array of messages
        $messages = data_get($this->payload, 'data.messages') 
            ?? data_get($this->payload, 'data', []);

        foreach ($messages as $messageData) {
            $messageId = data_get($messageData, 'key.id');

            if (! $messageId) {
                logOnDebug('No message id found', ['messageData' => $messageData]);
                continue;
            }

            $log = CampaignLog::query()
                ->where('module', 'whatsapp-web')
                ->where('meta->response->data->message->key->id', $messageId)
                ->first();

            if (! $log) {
                logOnDebug('No log found for message', ['messageId' => $messageId]);
                continue;
            }

            // Status can be in update.status (MESSAGES_UPDATE) or status (MESSAGES_UPSERT)
            $status = data_get($messageData, 'update.status') 
                ?? data_get($messageData, 'status');

            if (! $status) {
                logOnDebug('No status found', ['messageId' => $messageId]);
                continue;
            }

            // Map WhatsApp status to database columns
            // String values: PENDING, SERVER_ACK, DELIVERY_ACK, READ, PLAYED
            // Numeric values: 0 (PENDING), 1 (SERVER_ACK), 2 (DELIVERY_ACK), 3 (READ), 4 (PLAYED)
            match ($status) {
                'PENDING', 0 => $log->update(['send_at' => now()]),
                'SERVER_ACK', 1 => $log->update(['send_at' => now()]),
                'DELIVERY_ACK', 2 => $log->update(['delivered_at' => now()]),
                'READ', 3 => $log->update(['read_at' => now()]),
                'PLAYED', 4 => $log->update(['read_at' => now()]),
                default => logOnDebug('Unknown status value', ['status' => $status, 'messageId' => $messageId]),
            };
        }
    }
}
