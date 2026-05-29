<?php

namespace Modules\WhatsappWeb\App\Jobs;

use App\Models\Campaign;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\WhatsappWeb\App\Services\CampaignService;

class CampaignSendJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        CampaignService::send($this->campaign);
    }
}
