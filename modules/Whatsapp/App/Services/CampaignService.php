<?php

namespace Modules\Whatsapp\App\Services;

use App\Models\Campaign;
use App\Models\CampaignLog;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Template;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    /**
     * Kirim campaign dengan aturan anti-ban:
     *  1. Delay random per nomor (delay_min – delay_max detik)
     *  2. Batch pause setiap batch_size nomor (batch_pause_min – batch_pause_max menit)
     *  3. Daily limit per platform
     *  4. Filter kata spam (opsional)
     *  5. Resume: lanjutkan dari nomor terakhir yang belum terkirim
     */
    public static function send(Campaign $campaign): void
    {
        $customers = collect($campaign->group?->customers ?? []);

        if ($customers->isEmpty()) {
            $campaign->update(['status' => Campaign::STATUS_SEND]);
            return;
        }

        // ── Ambil pengaturan anti-ban dari campaign (dengan default aman) ──
        $delayMin      = max(1, (int) ($campaign->delay_min      ?? 8));
        $delayMax      = max($delayMin, (int) ($campaign->delay_max  ?? 15));
        $batchSizeMin  = max(1, (int) ($campaign->batch_size_min  ?? 20));
        $batchSizeMax  = max($batchSizeMin, (int) ($campaign->batch_size_max ?? 30));
        $batchPauseMin = max(1, (int) ($campaign->batch_pause_min ?? 5));   // menit
        $batchPauseMax = max($batchPauseMin, (int) ($campaign->batch_pause_max ?? 10)); // menit
        $dailyLimit    = max(1, (int) ($campaign->daily_limit    ?? 150));
        $spamFilter    = (bool) ($campaign->spam_filter ?? true);

        // ── Resume: skip customer yang sudah punya CampaignLog ──
        $alreadySentCustomerIds = $campaign->logs()->pluck('customer_id')->toArray();
        $pendingCustomers = $customers->filter(
            fn($c) => !in_array($c->id, $alreadySentCustomerIds)
        )->values();

        if ($pendingCustomers->isEmpty()) {
            $campaign->update(['status' => Campaign::STATUS_SEND]);
            return;
        }

        // ── Update status → pending (sedang berjalan) ──
        $campaign->update(['status' => Campaign::STATUS_PENDING]);

        // ── Hitung max_execution_time ──
        // worst case: setiap nomor delay_max detik + batch_pause_max menit per batch
        $estimatedSeconds = $pendingCustomers->count() * $delayMax
            + ceil($pendingCustomers->count() / $batchSizeMin) * ($batchPauseMax * 60);
        ini_set('max_execution_time', $estimatedSeconds + 300);

        // ── Tentukan batch size untuk sesi ini (random sekali per eksekusi) ──
        $batchSize = rand($batchSizeMin, $batchSizeMax);

        $sentToday  = 0;   // counter pesan terkirim di sesi ini
        $sentTotal  = 0;   // total berhasil kirim sesi ini
        $pausedDueToDailyLimit = false;

        foreach ($pendingCustomers as $index => $customer) {

            // ── [1] Cek daily limit per platform ──
            $sentTodayForPlatform = CampaignLog::whereHas('campaign', fn($q) =>
                $q->where('platform_id', $campaign->platform_id)
            )
                ->whereDate('send_at', today())
                ->count();

            if ($sentTodayForPlatform >= $dailyLimit) {
                Log::info("[Campaign #{$campaign->id}] Daily limit {$dailyLimit} tercapai. Campaign dijeda.");
                $pausedDueToDailyLimit = true;
                break;
            }

            // ── [2] Batch pause: istirahat setiap $batchSize nomor ──
            if ($index > 0 && $index % $batchSize === 0) {
                $pauseSeconds = rand($batchPauseMin * 60, $batchPauseMax * 60);
                Log::info("[Campaign #{$campaign->id}] Batch ke-" . ceil($index / $batchSize) . ": istirahat {$pauseSeconds} detik...");
                sleep($pauseSeconds);

                // Refresh batchSize untuk batch berikutnya
                $batchSize = rand($batchSizeMin, $batchSizeMax);
            }

            // ── [3] Buat/ambil conversation ──
            $conversation = Conversation::firstOrCreate([
                'module'      => 'whatsapp',
                'platform_id' => $campaign->platform_id,
                'owner_id'    => $campaign->owner_id,
                'customer_id' => $customer->id,
            ], [
                'badge_id'           => null,
                'auto_reply_enabled' => $campaign->platform->isAutoReplyEnabled(),
                'meta'               => [],
            ]);

            try {
                // ── [4] Siapkan pesan ──
                if (in_array($campaign->message_type, ['template', 'interactive'])) {
                    $template = new Template([
                        'module'   => $campaign->module,
                        'owner_id' => $campaign->owner_id,
                        'name'     => data_get($campaign, 'meta.name'),
                        'meta'     => $campaign->meta,
                        'type'     => $campaign->message_type,
                    ]);

                    $templateService = new TemplateService($template);
                    $message = $templateService->generateMessage($conversation, $customer);
                } else {
                    // ── [5] Filter spam pada teks ──
                    $messageBody = $campaign->meta;
                    if ($spamFilter && isset($messageBody['body']) && is_string($messageBody['body'])) {
                        $messageBody['body'] = SpamWordFilter::filter($messageBody['body']);
                    }

                    $message = new Message([
                        'module'          => 'whatsapp',
                        'uuid'            => null,
                        'conversation_id' => $conversation->id,
                        'platform_id'     => $conversation->platform_id,
                        'customer_id'     => $conversation->customer_id,
                        'owner_id'        => $conversation->owner_id,
                        'direction'       => 'out',
                        'type'            => $campaign->message_type,
                        'body'            => $messageBody,
                    ]);
                }

                // ── [6] Kirim pesan ──
                $messageService = new MessageService($message);
                $sendMessage    = $messageService->send();

                // ── [7] Catat log berhasil ──
                CampaignLog::create([
                    'module'      => 'whatsapp',
                    'owner_id'    => $conversation->owner_id,
                    'campaign_id' => $campaign->id,
                    'message_id'  => $sendMessage->id,
                    'customer_id' => $customer->id,
                    'send_at'     => now(),
                    'meta'        => [
                        'phone' => $customer->uuid,
                        'wamid' => $sendMessage->uuid,
                    ],
                ]);

                // Update progress di DB (untuk keperluan resume)
                $campaign->increment('sending_progress');
                $sentTotal++;
                $sentToday++;

            } catch (\Exception $e) {
                // Catat log gagal — jangan hentikan campaign, lanjut ke nomor berikutnya
                Log::error("[Campaign #{$campaign->id}] Gagal kirim ke customer #{$customer->id}: " . $e->getMessage());

                CampaignLog::create([
                    'module'      => 'whatsapp',
                    'owner_id'    => $campaign->owner_id,
                    'campaign_id' => $campaign->id,
                    'message_id'  => null,
                    'customer_id' => $customer->id,
                    'failed_at'   => now(),
                    'meta'        => [
                        'phone' => $customer->uuid,
                        'error' => $e->getMessage(),
                    ],
                ]);
            }

            // ── [8] Delay random antar nomor ──
            $delay = rand($delayMin, $delayMax);
            sleep($delay);
        }

        // ── Update status akhir campaign ──
        if ($pausedDueToDailyLimit) {
            // Daily limit tercapai — tandai paused agar bisa dilanjutkan besok
            $campaign->update(['status' => Campaign::STATUS_PAUSED]);
        } else {
            // Semua nomor sudah diproses
            $campaign->update([
                'status'           => Campaign::STATUS_SEND,
                'sending_progress' => $campaign->group->customers()->count(),
            ]);
        }
    }
}
