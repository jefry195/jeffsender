<?php

namespace App\Jobs;

use App\Events\IncomingNewMessageEvent;
use App\Models\Conversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IncomingMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $message
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        broadcast(new IncomingNewMessageEvent($this->message));

        $conversation = Conversation::findOrFail($this->message['conversation_id']);
        $lastMessageSent = $conversation->getMeta('last_message_at');

        $isAutoReplyDisabled = ! $conversation->isAutoReplyEnabled();
        $is24HoursPassed = $lastMessageSent && now()->diffInHours($lastMessageSent) > 24;
        $isPlatformEnabled = $conversation->platform?->isAutoReplyEnabled();

        if ($isAutoReplyDisabled && $is24HoursPassed && $isPlatformEnabled) {
            $conversation->update(['auto_reply_enabled' => true]);
        }

        $conversation->update([
            'meta' => array_merge($conversation->meta ?? [], [
                'last_message_at' => now(),
            ]),
        ]);
    }
}
