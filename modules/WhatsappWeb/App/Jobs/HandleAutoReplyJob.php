<?php

namespace Modules\WhatsappWeb\App\Jobs;

use App\Models\Chat;
use App\Models\Platform;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\WhatsappWeb\App\Services\AutoReplyService;

class HandleAutoReplyJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $messageText,
        public Platform $platform,
        public string $jid
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $chat = Chat::query()
            ->where('sessionId', $this->platform->uuid)
            ->where('id', $this->jid)
            ->first();

        if (! $chat) {
            // Fallback: try to find by phone number (removing domain)
            $phoneNumber = str($this->jid)->before('@')->before(':')->toString();
            $chat = Chat::query()
                ->where('sessionId', $this->platform->uuid)
                ->where('id', 'like', $phoneNumber . '%')
                ->first();
        }

        if (! $chat) {
            logOnDebug('WhatsappWeb: Chat not found for JID ' . $this->jid);

            return;
        }


        $autoReplyService = new AutoReplyService(
            $this->messageText,
            $this->platform,
            $chat
        );
        $autoReplyService->handleAutoReply();
    }
}
