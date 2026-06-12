<?php

namespace Modules\WhatsappWeb\App\Services;

use App\Helpers\ModuleServiceResolver;
use App\Models\AutoReply;
use App\Models\Chat;
use App\Models\Platform;
use Modules\WhatsappWeb\App\Jobs\SendMessageJob;

class AutoReplyService
{
    public function __construct(
        public string $messageText,
        public Platform $platform,
        public Chat $chat
    ) {}

    public function handleAutoReply()
    {

        if ($this->sendWelcomeMessage()) {
            logOnDebug('welcome message sent');

            return;
        }

        if (! $this->isAutoReplyEnabled()) {
            logOnDebug('auto reply not enabled');

            return;
        }

        // Conversational Custom Box Calculator Interception
        $stateKey = 'calc_state_' . $this->chat->id;
        $cleanMsg = strtolower(trim($this->messageText));
        if (\Cache::has($stateKey)) {
            $this->handleCalculatorFlow(\Cache::get($stateKey), $cleanMsg);
            return;
        }

        // Calculator keywords — also handles list row selection from menu utama
        $calcKeywords = [
            'kalkulator', '/kalkulator', 'hitung box', 'kalkulator box', 'custom box',
            'doorenz_box_custom', 'kemasan box custom', 'cetak box custom',
        ];
        if (in_array($cleanMsg, $calcKeywords)) {
            $this->startCalculatorFlow();
            return;
        }

        $autoReplyMethod = $this->platform->getMeta('auto_reply_method');

        match ($autoReplyMethod) {
            'default' => $this->handleDefaultReply(),
            default => $this->handleModuleAutoReply(),
        };

    }

    public function handleModuleAutoReply()
    {
        $moduleName = $this->platform->getMeta('auto_reply_method');
        $datasetId = $this->platform->getMeta('auto_reply_dataset', 0);
        $messageText = $this->messageText;

        if (! $moduleName || ! $datasetId || ! $messageText) {
            logOnDebug('auto reply module or dataset or message not found', [
                'moduleName' => $moduleName,
                'datasetId' => $datasetId,
                'messageText' => $messageText,
            ]);

            return;
        }

        $messages = ModuleServiceResolver::resolveReplyService($moduleName)
            ->using($datasetId, $messageText, [
                'module' => 'whatsapp-web',
                'chat_id' => $this->chat->id,
                'platform_uuid' => $this->platform->uuid,
            ])
            ->process()
            ->getMessages();

        foreach ($messages as $message) {
            $this->dispatchMessage(
                $message['body'],
                'number',
                $message['type']);
        }
    }

    public function sendWelcomeMessage(): bool
    {
        $platform = $this->platform;

        if (! $platform) {
            return false;
        }

        $lastMessageSendAt = $this->chat->wlc_mgs_send_at;
        if (! $lastMessageSendAt) {
            $lastMessageSendAt = now()->subHours(25);
        } else {
            $lastMessageSendAt = \Illuminate\Support\Carbon::createFromTimestamp($lastMessageSendAt);
        }

        $is24hPassed = now()->diffInHours($lastMessageSendAt, true) > 24;

        if (! $is24hPassed) {
            return false;
        }

        $autoReplyEnabled = $platform->getMeta('send_auto_reply', false);
        $sendWelcomeMessage = $platform->getMeta('send_welcome_message', false);
        $welcomeMessageTemplate = $platform->getMeta('welcome_message_template', '');

        if (! $autoReplyEnabled || ! $sendWelcomeMessage || ! $welcomeMessageTemplate) {
            return false;
        }

        if (preg_match('/\[template:(\d+)\]/', trim($welcomeMessageTemplate), $matches)) {
            $templateId = $matches[1];
            $template = \Illuminate\Support\Facades\DB::table('templates')->where('id', $templateId)->first();
            if ($template) {
                $message = json_decode($template->meta, true);
                $this->dispatchMessage(
                    $message,
                    'number',
                    $template->type,
                    isWelcomeMessage: true
                );
                return true;
            }
        }

        $this->dispatchMessage([
            'text' => $welcomeMessageTemplate,
        ], isWelcomeMessage: true);

        return true;
    }

    private function handleDefaultReply()
    {
        $bestMatch = $this->findBestMatch($this->messageText);
        if (! $bestMatch) {
            return false;
        }
        $textMessage = $bestMatch->message_template;
        $messageType = $bestMatch->message_type;

        $message = [
            'text' => $textMessage,
        ];

        if ($messageType == 'template') {
            $template = $bestMatch->template;
            $message = $template->meta;
            $messageType = $template->type;
        }

        $this->dispatchMessage(
            $message,
            'number',
            $messageType
        );

        return true;
    }

    // helper methods
    private function isAutoReplyEnabled(): bool
    {
        return $this->platform &&
            $this->platform->isAutoReplyEnabled() &&
            $this->chat->isAutoReplyEnabled();
    }

    private function findBestMatch(string $searchQuery): ?AutoReply
    {
        $searchTerms = explode(' ', strtolower($searchQuery));
        $searchTerms[] = strtolower($searchQuery);

        \Log::debug('WhatsappWeb: Matching keywords', [
            'searchTerms' => $searchTerms,
            'owner_id' => $this->platform->owner_id
        ]);

        $potentialMatches = AutoReply::query()
            ->where('module', 'whatsapp-web')
            ->where('owner_id', $this->platform->owner_id)
            ->matchKeywords($searchTerms)
            ->get();

        \Log::debug('WhatsappWeb: Potential matches found', [
            'count' => $potentialMatches->count(),
            'ids' => $potentialMatches->pluck('id')
        ]);

        $bestMatch = null;
        $maxMatchCount = 0;

        foreach ($potentialMatches as $potentialMatch) {
            $matchCount = count(array_intersect(
                $searchTerms,
                array_map('strtolower', $potentialMatch->keywords)
            ));

            if ($matchCount > $maxMatchCount) {
                $maxMatchCount = $matchCount;
                $bestMatch = $potentialMatch;
            }
        }

        return $bestMatch;
    }

    private function dispatchMessage(array $message, $sendType = 'number', $messageType = 'text', $isWelcomeMessage = false)
    {
        if ($messageType == 'text') {
            $message['text'] = $this->replaceShortCodes($message['text'] ?? '');
        }

        dispatch(
            new SendMessageJob(
                $this->platform->uuid,
                $this->chat->id,
                $message,
                $messageType,
                $sendType,
                $isWelcomeMessage
            )
        );
    }

    private function replaceShortCodes($text)
    {
        $platformUuid = $this->platform?->uuid ?? '';
        $baseUrl = rtrim(config('app.url', 'http://127.0.0.1:8010'), '/');
        $orderLink = $platformUuid ? "{$baseUrl}/order/{$platformUuid}" : "{$baseUrl}/order";

        return str_replace(
            ['{name}', '{platform_uuid}', '{order_link}'],
            [$this->chat?->name ?? '{name}', $platformUuid, $orderLink],
            $text
        );
    }

    private function startCalculatorFlow()
    {
        $text = "📦 *Kalkulator Kemasan Box Custom Dooren'z*\n\n" .
                "Silakan pilih jenis produk yang ingin dicetak:\n" .
                "1. Lunch Box\n" .
                "2. Rice Box\n" .
                "3. Dine In (Nampan Kertas)\n" .
                "4. Kotak Tutup Terpisah\n" .
                "5. Mailer (Kotak Sambung)\n" .
                "6. Straight Tuck End (STE)\n" .
                "7. Kemasan Kebab\n" .
                "8. Kotak Mug\n" .
                "9. Kotak Burger\n\n" .
                "*Balas dengan mengetik nomor pilihan Kakak (misal: 1 atau 2):*";

        \Cache::put('calc_state_' . $this->chat->id, ['step' => 'choose_product'], 1800);
        $this->dispatchMessage(['text' => $text]);
    }

    private function handleCalculatorFlow(array $state, string $msg)
    {
        $step = $state['step'] ?? '';
        $stateKey = 'calc_state_' . $this->chat->id;

        // Cancel command
        if ($msg === 'batal' || $msg === '/batal' || $msg === 'cancel') {
            \Cache::forget($stateKey);
            $this->dispatchMessage(['text' => "❌ *Kalkulator dibatalkan.* Silakan ketik *kalkulator* kembali jika ingin menghitung ulang."]);
            return;
        }

        switch ($step) {
            case 'choose_product':
                $num = (int)$msg;
                $types = [
                    1 => 'lunchBox',
                    2 => 'riceBox',
                    3 => 'dineIn',
                    4 => 'kotakTutupTerpisah',
                    5 => 'kotakSambung',
                    6 => 'straightTuckEnd',
                    7 => 'kebab',
                    8 => 'kotakMug',
                    9 => 'burger'
                ];

                if (!isset($types[$num])) {
                    $this->dispatchMessage(['text' => "⚠️ Pilihan tidak valid. Silakan balas dengan angka *1 sampai 9* sesuai menu di atas, atau ketik *batal* untuk keluar."]);
                    return;
                }

                $type = $types[$num];
                $productName = \App\Services\BoxCalculatorService::getProductTypes()[$type];

                $state['type'] = $type;
                $state['step'] = 'get_dimensions';
                \Cache::put($stateKey, $state, 1800);

                // Ask for dimensions based on product type
                $dimPrompt = "";
                if ($type === 'lunchBox' || $type === 'riceBox') {
                    $dimPrompt = "📏 *Langkah 2: Masukkan Ukuran {$productName}*\n\n" .
                                 "Masukkan ukuran dengan format:\n*P Atas, L Atas, P Bawah, L Bawah, Tinggi, Tutup* (dalam cm, dipisah koma).\n\n" .
                                 "Contoh: *18,10.5,16,8.5,5,2.5*";
                } elseif ($type === 'dineIn') {
                    $dimPrompt = "📏 *Langkah 2: Masukkan Ukuran {$productName}*\n\n" .
                                 "Masukkan ukuran dengan format:\n*Panjang, Lebar, Tinggi* (dalam cm, dipisah koma).\n\n" .
                                 "Contoh: *18,10,4*";
                } elseif ($type === 'kotakTutupTerpisah') {
                    $dimPrompt = "📏 *Langkah 2: Masukkan Ukuran {$productName}*\n\n" .
                                 "Masukkan ukuran dengan format:\n*P Bawah, L Bawah, T Bawah, P Atas, L Atas, T Atas* (dalam cm, dipisah koma).\n\n" .
                                 "Contoh: *23,33,33,18,18,7.5*";
                } elseif ($type === 'kotakSambung') {
                    $dimPrompt = "📏 *Langkah 2: Masukkan Ukuran {$productName}*\n\n" .
                                 "Masukkan ukuran dengan format:\n*Panjang, Lebar, Tinggi, Tutup* (dalam cm, dipisah koma).\n\n" .
                                 "Contoh: *14,10.5,6.5,3.5*";
                } elseif ($type === 'straightTuckEnd') {
                    $dimPrompt = "📏 *Langkah 2: Masukkan Ukuran STE*\n\n" .
                                 "Masukkan ukuran dengan format:\n*Panjang, Lebar, Tinggi, Kuping Lem* (dalam cm, dipisah koma).\n\n" .
                                 "Contoh: *10,5,15,1.5*";
                } elseif ($type === 'kebab') {
                    $dimPrompt = "📏 *Langkah 2: Masukkan Ukuran Kebab*\n\n" .
                                 "Masukkan ukuran dengan format:\n*Panjang, Lebar, Lidah Lem* (dalam cm, dipisah koma).\n\n" .
                                 "Contoh: *26,9,1.5*";
                } elseif ($type === 'kotakMug') {
                    $dimPrompt = "📏 *Langkah 2: Masukkan Ukuran Kotak Mug*\n\n" .
                                 "Masukkan ukuran dengan format:\n*Panjang, Lebar, Tinggi, Lidah Lem, Kunci Bawah* (dalam cm, dipisah koma).\n\n" .
                                 "Contoh: *8,11,10,1.3,8*";
                } elseif ($type === 'burger') {
                    $dimPrompt = "📏 *Langkah 2: Masukkan Ukuran Kotak Burger*\n\n" .
                                 "Masukkan ukuran dengan format:\n*Panjang, Lebar, T Bawah, T Karkasan, T Tutup* (dalam cm, dipisah koma).\n\n" .
                                 "Contoh: *10,10,4,5,5*";
                }

                $this->dispatchMessage(['text' => $dimPrompt]);
                break;

            case 'get_dimensions':
                // Parse and validate dimensions
                $parts = explode(',', str_replace(' ', '', $msg));
                $type = $state['type'];
                
                $expectedCounts = [
                    'lunchBox' => 6,
                    'riceBox' => 6,
                    'dineIn' => 3,
                    'kotakTutupTerpisah' => 6,
                    'kotakSambung' => 4,
                    'straightTuckEnd' => 4,
                    'kebab' => 3,
                    'kotakMug' => 5,
                    'burger' => 5
                ];
                
                $expected = $expectedCounts[$type] ?? 3;
                if (count($parts) !== $expected) {
                    $this->dispatchMessage(['text' => "⚠️ Format ukuran salah. Kakak harus memasukkan *{$expected} angka* dipisahkan koma. Silakan coba lagi, atau ketik *batal* untuk keluar."]);
                    return;
                }

                // Map dimensions into a named array
                $dimArray = [];
                if ($type === 'lunchBox' || $type === 'riceBox') {
                    $dimArray = ['p_atas' => $parts[0], 'l_atas' => $parts[1], 'p_bawah' => $parts[2], 'l_bawah' => $parts[3], 't' => $parts[4], 'tutup' => $parts[5]];
                } elseif ($type === 'dineIn') {
                    $dimArray = ['p' => $parts[0], 'l' => $parts[1], 't' => $parts[2]];
                } elseif ($type === 'kotakTutupTerpisah') {
                    $dimArray = ['p_bawah' => $parts[0], 'l_bawah' => $parts[1], 't_bawah' => $parts[2], 'p_atas' => $parts[3], 'l_atas' => $parts[4], 't_atas' => $parts[5]];
                } elseif ($type === 'kotakSambung') {
                    $dimArray = ['p' => $parts[0], 'l' => $parts[1], 't' => $parts[2], 'tutup' => $parts[3]];
                } elseif ($type === 'straightTuckEnd') {
                    $dimArray = ['p' => $parts[0], 'l' => $parts[1], 't' => $parts[2], 'lem' => $parts[3]];
                } elseif ($type === 'kebab') {
                    $dimArray = ['p' => $parts[0], 'l' => $parts[1], 'lem' => $parts[2]];
                } elseif ($type === 'kotakMug') {
                    $dimArray = ['p' => $parts[0], 'l' => $parts[1], 't' => $parts[2], 'lem' => $parts[3], 'kunci_bawah' => $parts[4]];
                } elseif ($type === 'burger') {
                    $dimArray = ['p' => $parts[0], 'l' => $parts[1], 't_bawah' => $parts[2], 't_krkn' => $parts[3], 't_tutup' => $parts[4]];
                }

                $state['dimensions'] = $dimArray;
                $state['step'] = 'get_qty';
                \Cache::put($stateKey, $state, 1800);

                $this->dispatchMessage(['text' => "🔢 *Langkah 3: Jumlah Cetak (Pcs)*\n\n" .
                                                 "Masukkan jumlah pcs yang ingin Kakak cetak (Contoh: *1000*):"]);
                break;

            case 'get_qty':
                $qty = (int)$msg;
                if ($qty <= 0) {
                    $this->dispatchMessage(['text' => "⚠️ Jumlah cetak harus berupa angka positif. Silakan masukkan angka yang valid (Contoh: *1000*):"]);
                    return;
                }

                $state['qty'] = $qty;
                $state['step'] = 'get_material';
                \Cache::put($stateKey, $state, 1800);

                $this->dispatchMessage(['text' => "📄 *Langkah 4: Pilih Bahan Kertas*\n\n" .
                                                 "Silakan pilih bahan kertas yang ingin digunakan:\n" .
                                                 "1. Kraft 290gr (Warna coklat, ramah lingkungan, ekonomis)\n" .
                                                 "2. Ivory 250gr (Warna putih semi-glossy, premium)\n" .
                                                 "3. Duplex 250gr (Warna putih depan, abu belakang, ekonomis)\n\n" .
                                                 "*Ketik nomor pilihan Kakak (1, 2, atau 3):*"]);
                break;

            case 'get_material':
                $num = (int)$msg;
                $materials = [
                    1 => 'kraft290_off',
                    2 => 'ivory250_off',
                    3 => 'duplex250_off'
                ];

                if (!isset($materials[$num])) {
                    $this->dispatchMessage(['text' => "⚠️ Pilihan bahan tidak valid. Ketik angka *1, 2, atau 3* sesuai menu di atas:"]);
                    return;
                }

                $state['material'] = $materials[$num];
                $state['step'] = 'get_laminasi';
                \Cache::put($stateKey, $state, 1800);

                $this->dispatchMessage(['text' => "✨ *Langkah 5: Pilih Laminasi*\n\n" .
                                                 "Apakah kemasan ingin menggunakan laminasi dalam (anti air/minyak)?\n" .
                                                 "1. Tanpa Laminasi\n" .
                                                 "2. Laminasi Glossy (Mengkilap)\n" .
                                                 "3. Laminasi Doff (Matte)\n\n" .
                                                 "*Ketik nomor pilihan Kakak (1, 2, atau 3):*"]);
                break;

            case 'get_laminasi':
                $num = (int)$msg;
                $laminations = [
                    1 => 'none',
                    2 => 'glossy',
                    3 => 'doff'
                ];

                if (!isset($laminations[$num])) {
                    $this->dispatchMessage(['text' => "⚠️ Pilihan laminasi tidak valid. Ketik angka *1, 2, atau 3* sesuai menu di atas:"]);
                    return;
                }

                $laminasi = $laminations[$num];
                $type = $state['type'];
                $dimensions = $state['dimensions'];
                $qty = $state['qty'];
                $material = $state['material'];

                // Run calculation!
                try {
                    $res = \App\Services\BoxCalculatorService::calculatePrice($type, $dimensions, $qty, $material, $laminasi);
                    
                    $prodName = \App\Services\BoxCalculatorService::getProductTypes()[$type];
                    $matName = \App\Services\BoxCalculatorService::getBahanOptions()[$material];
                    $lamName = \App\Services\BoxCalculatorService::getLaminasiOptions()[$laminasi];
                    
                    $resultText = "📊 *Hasil Perhitungan Harga Box Custom Dooren'z*\n\n" .
                                  "• *Tipe Kemasan:* {$prodName}\n" .
                                  "• *Kertas Bahan:* {$matName}\n" .
                                  "• *Laminasi:* {$lamName}\n" .
                                  "• *Jumlah Cetak:* " . number_format($qty, 0, ',', '.') . " pcs\n" .
                                  "• *Kertas Plano:* {$res['plano_size']}\n" .
                                  "• *Muat Per Plano:* {$res['items_per_plano']} pcs\n" .
                                  "• *Total Kertas Plano:* " . number_format($res['total_plano'], 0, ',', '.') . " lembar\n\n" .
                                  "💰 *ESTIMASI HARGA JUAL:*\n" .
                                  "• *Harga Satuan:* *Rp " . number_format($res['harga_satuan'], 0, ',', '.') . "/pcs*\n" .
                                  "• *Total Harga:* *Rp " . number_format($res['total_jual'], 0, ',', '.') . "*\n\n" .
                                  "⚠️ *Catatan:* Harga di atas merupakan *harga estimasi saja (tidak mengikat)* dan dapat berubah sewaktu-waktu mengikuti harga bahan baku di pasar. Admin kami juga akan melakukan *pengecekan ketersediaan stok* terlebih dahulu sebelum pesanan Kakak diproses.";
                                  
                    $this->dispatchMessage(['text' => $resultText]);
                } catch (\Throwable $th) {
                    \Log::error('Calculator Flow Error: ' . $th->getMessage());
                    $this->dispatchMessage(['text' => "❌ Terjadi kesalahan saat menghitung harga. Mohon ulangi kembali dengan mengetik *kalkulator*."]);
                }

                // Clear state
                \Cache::forget($stateKey);
                break;
        }
    }
}
