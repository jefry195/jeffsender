<?php

namespace App\Jobs;

use App\Events\IncomingNewMessageEvent;
use App\Helpers\ModuleServiceResolver;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $messageAttrs
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $message = new Message($this->messageAttrs);
        $sendMessage = ModuleServiceResolver::resolveMessageService($message)
            ->replaceShortcode()
            ->send();

        if ($sendMessage?->uuid) {
            $conversation = $sendMessage->conversation;
            $conversation->update([
                'meta' => array_merge($conversation->meta ?? [], [
                    'last_message_at' => now(),
                ]),
            ]);
            try {
                broadcast(new IncomingNewMessageEvent($sendMessage->toArray()));
            } catch (\Exception $e) {
                // Log the exception or handle it as needed
                Log::error('Broadcasting IncomingNewMessageEvent failed: '.$e->getMessage());
            }
        }
    }
}
