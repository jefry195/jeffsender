<?php

namespace Modules\WhatsappWeb\App\Http\Controllers;

use App\Helpers\PageHeader;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignLog;
use App\Models\Group;
use Illuminate\Http\Request;
use Modules\Whatsapp\App\Services\SpamWordFilter;

class SimulationController extends Controller
{
    /**
     * Halaman simulasi campaign — test semua fitur anti-ban tanpa kirim pesan
     */
    public function index(Request $request)
    {
        PageHeader::set()
            ->title('🧪 Simulasi Campaign')
            ->buttons([
                [
                    'url'  => route('user.whatsapp-web.campaigns.index'),
                    'text' => 'Kembali ke Kampanye',
                ],
            ]);

        $groups   = activeWorkspaceOwner()->groups()->whatsappWeb()->get(['id', 'name']);
        $campaigns = activeWorkspaceOwner()->campaigns()->whatsappWeb()->latest()->get(['id', 'name', 'status']);

        return inertia('Simulation/Index', compact('groups', 'campaigns'));
    }

    /**
     * Jalankan simulasi dan kembalikan hasil JSON (dipakai oleh Vue via axios)
     */
    public function run(Request $request)
    {
        $request->validate([
            'group_id'       => 'required|exists:groups,id',
            'message'        => 'required|string',
            'delay_min'      => 'required|integer|min:1|max:60',
            'delay_max'      => 'required|integer|min:1|max:60',
            'batch_size_min' => 'required|integer|min:1|max:500',
            'batch_size_max' => 'required|integer|min:1|max:500',
            'batch_pause_min'=> 'required|integer|min:1|max:60',
            'batch_pause_max'=> 'required|integer|min:1|max:60',
            'daily_limit'    => 'required|integer|min:1|max:1000',
            'spam_filter'    => 'boolean',
            'campaign_id'    => 'nullable|exists:campaigns,id',
        ]);

        $group     = Group::with('customers:id,name,uuid')->findOrFail($request->group_id);
        $customers = collect($group->customers);

        $delayMin      = (int) $request->delay_min;
        $delayMax      = (int) $request->delay_max;
        $batchSizeMin  = (int) $request->batch_size_min;
        $batchSizeMax  = (int) $request->batch_size_max;
        $batchPauseMin = (int) $request->batch_pause_min;
        $batchPauseMax = (int) $request->batch_pause_max;
        $dailyLimit    = (int) $request->daily_limit;
        $spamFilter    = (bool) ($request->spam_filter ?? true);
        $messageText   = $request->message;

        // ── [1] TEST SPAM FILTER ──
        $filteredMessage = $spamFilter ? SpamWordFilter::filter($messageText) : $messageText;
        $detectedWords   = $spamFilter ? SpamWordFilter::detect($messageText) : [];

        // ── [2] DAILY LIMIT CHECK ──
        $sentToday = 0;
        if ($request->campaign_id) {
            $sentToday = CampaignLog::whereHas('campaign', fn($q) =>
                $q->where('id', $request->campaign_id)
            )->whereDate('send_at', today())->count();
        }
        $remaining = max(0, $dailyLimit - $sentToday);

        // ── [3] RESUME CHECK (skip yang sudah terkirim) ──
        $alreadySentIds = [];
        if ($request->campaign_id) {
            $alreadySentIds = CampaignLog::where('campaign_id', $request->campaign_id)
                ->whereNotNull('send_at')
                ->pluck('customer_id')
                ->toArray();
        }

        // ── [4] SIMULASI BATCH ──
        $batchSize    = rand($batchSizeMin, $batchSizeMax);
        $batches      = [];
        $currentBatch = [];
        $batchIndex   = 1;
        $totalDelay   = 0;
        $customerList = [];

        foreach ($customers as $index => $customer) {
            $isAlreadySent  = in_array($customer->id, $alreadySentIds);
            $isOverLimit    = ($index - count($alreadySentIds)) >= $remaining;
            $delayThisMsg   = rand($delayMin, $delayMax);
            $totalDelay    += $delayThisMsg;

            $entry = [
                'no'          => $index + 1,
                'name'        => $customer->name,
                'phone'       => $customer->uuid,
                'batch'       => $batchIndex,
                'delay'       => $delayThisMsg,
                'skipped'     => $isAlreadySent ? 'sudah terkirim' : ($isOverLimit ? 'daily limit' : null),
                'status'      => $isAlreadySent ? 'skip_sent' : ($isOverLimit ? 'skip_limit' : 'akan_kirim'),
            ];

            $customerList[] = $entry;

            if (!$isAlreadySent && !$isOverLimit) {
                $currentBatch[] = $entry;
            }

            // Cek apakah perlu ganti batch
            $sentCount = $index - count($alreadySentIds) + 1;
            if ($sentCount > 0 && $sentCount % $batchSize === 0) {
                $pauseSeconds  = rand($batchPauseMin * 60, $batchPauseMax * 60);
                $batches[]     = [
                    'batch'        => $batchIndex,
                    'count'        => count($currentBatch),
                    'pause_after'  => $pauseSeconds,
                    'pause_label'  => gmdate('i \m\e\n\i\t s \d\e\t\i\k', $pauseSeconds),
                ];
                $totalDelay   += $pauseSeconds;
                $currentBatch  = [];
                $batchIndex++;
                $batchSize = rand($batchSizeMin, $batchSizeMax);
            }
        }

        // Tambah batch terakhir jika masih ada
        if (!empty($currentBatch)) {
            $batches[] = [
                'batch'       => $batchIndex,
                'count'       => count($currentBatch),
                'pause_after' => 0,
                'pause_label' => 'Selesai',
            ];
        }

        // ── [5] ESTIMASI TOTAL WAKTU ──
        $willSend    = $customers->filter(fn($c) => !in_array($c->id, $alreadySentIds))->count();
        $willSend    = min($willSend, $remaining);
        $estSeconds  = ($willSend * (($delayMin + $delayMax) / 2))
                     + (count($batches) * (($batchPauseMin + $batchPauseMax) / 2 * 60));
        $estHours    = floor($estSeconds / 3600);
        $estMinutes  = floor(($estSeconds % 3600) / 60);

        return response()->json([
            'summary' => [
                'total_customers'   => $customers->count(),
                'already_sent'      => count($alreadySentIds),
                'will_send'         => $willSend,
                'skipped_limit'     => max(0, $customers->count() - count($alreadySentIds) - $willSend),
                'daily_limit'       => $dailyLimit,
                'sent_today'        => $sentToday,
                'remaining_quota'   => $remaining,
                'total_batches'     => count($batches),
                'est_time'          => "{$estHours} jam {$estMinutes} menit",
            ],
            'spam_filter' => [
                'enabled'        => $spamFilter,
                'original'       => $messageText,
                'filtered'       => $filteredMessage,
                'detected_words' => $detectedWords,
                'changed'        => $messageText !== $filteredMessage,
            ],
            'batches'       => $batches,
            'customers'     => array_slice($customerList, 0, 50), // max 50 untuk tampilan
        ]);
    }
}
