<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\AutoReplyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class HandleAutoReplyJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Message $incomingMessage,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            AutoReplyService::for($this->incomingMessage)->sendWelcomeMessage()->sendAutoReply();
        } catch (\Throwable $th) {
            if (app()->hasDebugModeEnabled()) {
                throw $th;
            }
        }
    }
}
