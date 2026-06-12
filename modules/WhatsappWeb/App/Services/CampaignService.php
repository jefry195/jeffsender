<?php

namespace Modules\WhatsappWeb\App\Services;

use App\Models\Campaign;
use App\Models\CampaignLog;
use Illuminate\Support\Facades\Log;
use Modules\Whatsapp\App\Services\SpamWordFilter;

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
    public static function send(Campaign $campaign): array
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $customers = collect($campaign->group->customers ?? []);

        if ($customers->isEmpty()) {
            $campaign->update(['status' => Campaign::STATUS_SEND]);
            return [];
        }

        // ── Ambil pengaturan anti-ban dari campaign (dengan default aman) ──
        $delayBetween  = $campaign->delay_between ?? [8, 15];
        $delayMin      = max(1, (int) ($campaign->delay_min      ?? min($delayBetween)));
        $delayMax      = max($delayMin, (int) ($campaign->delay_max  ?? max($delayBetween)));
        $batchSizeMin  = max(1, (int) ($campaign->batch_size_min  ?? 20));
        $batchSizeMax  = max($batchSizeMin, (int) ($campaign->batch_size_max ?? 30));
        $batchPauseMin = max(1, (int) ($campaign->batch_pause_min ?? 5));   // menit
        $batchPauseMax = max($batchPauseMin, (int) ($campaign->batch_pause_max ?? 10)); // menit
        $dailyLimit    = max(1, (int) ($campaign->daily_limit    ?? 150));
        $spamFilter    = (bool) ($campaign->spam_filter ?? true);

        // ── Resume: skip customer yang sudah punya CampaignLog berhasil ──
        $alreadySentCustomerIds = $campaign->logs()
            ->whereNotNull('send_at')
            ->pluck('customer_id')
            ->toArray();

        $pendingCustomers = $customers->filter(
            fn($c) => !in_array($c->id, $alreadySentCustomerIds)
        )->values();

        if ($pendingCustomers->isEmpty()) {
            $campaign->update(['status' => Campaign::STATUS_SEND]);
            return [];
        }

        $whatsappClient = new WhatsAppWebService;
        $result = [];
        $batchSize = rand($batchSizeMin, $batchSizeMax);
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
                Log::info("[Campaign #{$campaign->id}] Batch ke-" . ceil($index / $batchSize) . ": istirahat {$pauseSeconds}s...");
                sleep($pauseSeconds);
                // Refresh batchSize untuk batch berikutnya
                $batchSize = rand($batchSizeMin, $batchSizeMax);
            }

            // ── [3] Siapkan payload pesan ──
            $payload = self::replaceShortCodes(
                $campaign->meta,
                ['{name}' => $customer->name]
            );

            // ── [4] Filter kata spam pada teks ──
            if ($spamFilter && isset($payload['text']) && is_string($payload['text'])) {
                $payload['text'] = SpamWordFilter::filter($payload['text']);
            }

            $sessionId = $campaign->platform->uuid;
            $jid = "{$customer->uuid}@s.whatsapp.net";

            // ── [5] Kirim pesan ──
            try {
                $res = $whatsappClient->sendMessage(
                    sessionId: $sessionId,
                    jid: $jid,
                    message: $payload,
                    messageType: $campaign->message_type,
                    type: 'number'
                );

                $result[] = [
                    'to'      => $customer->uuid,
                    'success' => $res->successful(),
                ];

                if ($res->failed() && env('APP_DEBUG')) {
                    Log::error('Campaign Message Failed', [
                        'request'  => $payload,
                        'response' => $res->json(),
                    ]);
                }

                if ($res->successful()) {
                    CampaignLog::create([
                        'module'      => $campaign->module,
                        'owner_id'    => $campaign->owner_id,
                        'campaign_id' => $campaign->id,
                        'customer_id' => $customer->id,
                        'meta'        => [
                            'request'  => $payload,
                            'response' => $res->json(),
                        ],
                        'send_at'      => now(),
                        'read_at'      => null,
                        'delivered_at' => null,
                        'failed_at'    => null,
                    ]);

                    // Update progress
                    $campaign->increment('sending_progress');
                }

            } catch (\Throwable $e) {
                Log::error("[Campaign #{$campaign->id}] Gagal kirim ke {$customer->uuid}: " . $e->getMessage());
                $result[] = ['to' => $customer->uuid, 'success' => false];
            }

            // ── [6] Delay random antar nomor ──
            $delay = rand($delayMin, $delayMax);
            sleep($delay);
        }

        // ── Update status akhir campaign ──
        if ($pausedDueToDailyLimit) {
            $campaign->update(['status' => Campaign::STATUS_PAUSED]);
        } else {
            $campaign->update(['status' => Campaign::STATUS_SEND]);
        }

        return $result;
    }

    /**
     * Replace short codes in the message body with actual values.
     */
    public static function replaceShortCodes(array $payload, array $replaceCodes): array
    {
        if (isset($payload['text'])) {
            $payload['text'] = str_replace(array_keys($replaceCodes), array_values($replaceCodes), $payload['text']);
        }
        return $payload;
    }
}
