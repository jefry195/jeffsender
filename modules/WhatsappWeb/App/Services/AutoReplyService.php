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

        // Conversational Digital Printing Interception
        $digStateKey = 'dig_state_' . $this->chat->id;
        if (\Cache::has($digStateKey)) {
            $this->handleDigitalPrintingFlow(\Cache::get($digStateKey), $cleanMsg);
            return;
        }

        // Calculator keywords — also handles list row selection from menu utama
        $calcKeywords = [
            'kalkulator', '/kalkulator', 'hitung box', 'kalkulator box', 'custom box',
            'doorenz_box_custom', 'kemasan box custom', 'cetak box custom',
            'hitung', 'kalkulasi', 'kalkulator kemasan',
        ];
        if (in_array($cleanMsg, $calcKeywords)) {
            $this->startCalculatorFlow();
            return;
        }

        // Digital Printing keywords
        $digKeywords = [
            'doorenz_digital_printing', 'cetak digital', 'digital printing',
            'cetak stiker', 'cetak brosur', 'cetak kartu nama', 'cetak voucher',
            'cetak member card', 'digital print',
        ];
        if (in_array($cleanMsg, $digKeywords)) {
            $this->startDigitalPrintingFlow();
            return;
        }

        $autoReplyMethod = $this->platform->getMeta('auto_reply_method');

        if ($autoReplyMethod === 'default') {
            $this->handleDefaultReply();
        } else {
            // For custom modules, send welcome message if enabled
            $sendWelcomeMessageSetting = $this->platform->getMeta('send_welcome_message', false);
            if ($sendWelcomeMessageSetting) {
                $this->sendWelcomeMessageDirect();
            }
            $this->handleModuleAutoReply();
        }

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

    public function sendWelcomeMessageDirect(): bool
    {
        $platform = $this->platform;
        $welcomeMessageTemplate = $platform->getMeta('welcome_message_template', '');
        if (! $welcomeMessageTemplate) {
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

    public function sendWelcomeMessage(): bool
    {
        $platform = $this->platform;

        if (! $platform) {
            return false;
        }

        $autoReplyEnabled = $platform->getMeta('send_auto_reply', false);
        $sendWelcomeMessage = $platform->getMeta('send_welcome_message', false);

        if (! $autoReplyEnabled || ! $sendWelcomeMessage) {
            return false;
        }

        return $this->sendWelcomeMessageDirect();
    }

    private function handleDefaultReply()
    {
        $bestMatch = $this->findBestMatch($this->messageText);

        // Retrieve welcome template info
        $welcomeTemplateId = null;
        $welcomeTemplateSetting = $this->platform->getMeta('welcome_message_template', '');
        if (preg_match('/\[template:(\d+)\]/', trim($welcomeTemplateSetting), $matches)) {
            $welcomeTemplateId = (int)$matches[1];
        }

        if ($bestMatch) {
            // Check if the matched auto-reply is sending the welcome message template
            $isSameAsWelcome = false;
            if ($welcomeTemplateId && $bestMatch->message_type === 'template' && $bestMatch->template_id == $welcomeTemplateId) {
                $isSameAsWelcome = true;
            } elseif ($welcomeTemplateSetting && $bestMatch->message_type === 'text' && trim($bestMatch->message_template) === trim($welcomeTemplateSetting)) {
                $isSameAsWelcome = true;
            }

            if ($isSameAsWelcome) {
                logOnDebug('AutoReply: Sending welcome message template via matched auto-reply');
                $this->sendWelcomeMessageDirect();
                return true;
            }

            // It is a DIFFERENT template/message. Check cache to send at most once per 24 hours per chat.
            // It is a DIFFERENT template/message. Send it directly without rate-limiting.
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

        // If no best match, check if Out of Office is enabled and active
        if ($this->platform->isOooMessageEnabled() && $this->isOutOfOperationalHours()) {
            $this->handleOutOfHoursReply();
            return true;
        }

        // If no best match, send welcome message if eligible
        $autoReplyEnabled = $this->platform->getMeta('send_auto_reply', false);
        $sendWelcomeMessage = $this->platform->getMeta('send_welcome_message', false);

        if ($autoReplyEnabled && $sendWelcomeMessage) {
            logOnDebug('AutoReply: No match, sending welcome message');
            $this->sendWelcomeMessageDirect();
            return true;
        }

        return false;
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
            ->active()
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
                "9. Kotak Burger\n" .
                "10. Custom Ukuran Datar\n\n" .
                "*Balas dengan mengetik nomor pilihan Kakak (misal: 1 atau 10):*";

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
                    9 => 'burger',
                    10 => 'customFlat'
                ];

                if (!isset($types[$num])) {
                    $this->dispatchMessage(['text' => "⚠️ Pilihan tidak valid. Silakan balas dengan angka *1 sampai 10* sesuai menu di atas, atau ketik *batal* untuk keluar."]);
                    return;
                }

                $type = $types[$num];
                $productName = \App\Services\BoxCalculatorService::getProductTypes()[$type];

                $state['type'] = $type;
                $state['step'] = 'choose_size';
                \Cache::put($stateKey, $state, 1800);

                // Preset sizes per product type
                $sizeMenuText = self::buildSizeMenu($type, $productName);
                $this->dispatchMessage(['text' => $sizeMenuText]);
                break;

            case 'choose_size':
                $type = $state['type'];
                $productName = \App\Services\BoxCalculatorService::getProductTypes()[$type];
                $presets = self::getPresets($type);
                $totalPresets = count($presets);

                // Cek apakah customer pilih "ukuran sendiri" (angka paling akhir)
                $num = (int)$msg;
                $customOption = $totalPresets + 1;

                if ($num === $customOption) {
                    // Customer mau input ukuran sendiri
                    $state['step'] = 'get_dimensions';
                    \Cache::put($stateKey, $state, 1800);

                    $dimPrompt = self::buildDimPrompt($type, $productName);
                    $this->dispatchMessage(['text' => $dimPrompt]);
                    return;
                }

                if ($num >= 1 && $num <= $totalPresets) {
                    // Customer pilih preset
                    $preset = $presets[$num - 1];
                    $state['dimensions'] = $preset['values'];
                    $state['step'] = 'get_qty';
                    \Cache::put($stateKey, $state, 1800);

                    $presetInfo = "✅ *Ukuran {$preset['label']}* dipilih.\n";
                    foreach ($preset['desc'] as $k => $v) {
                        $presetInfo .= "• {$k}: *{$v} cm*\n";
                    }
                    $presetInfo .= "\n🔢 *Langkah 3: Jumlah Cetak (Pcs)*\n\nMasukkan jumlah pcs yang ingin Kakak cetak (Contoh: *1000*):";
                    $this->dispatchMessage(['text' => $presetInfo]);
                    return;
                }

                // Input tidak valid
                $this->dispatchMessage(['text' => "⚠️ Pilihan tidak valid. Ketik angka *1 sampai {$customOption}* sesuai menu di atas, atau ketik *batal* untuk keluar."]);
                return;


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
                    'burger' => 5,
                    'customFlat' => 2
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
                } elseif ($type === 'customFlat') {
                    $dimArray = ['p' => $parts[0], 'l' => $parts[1]];
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

                // Use resolveIsDoorenz() for consistent workspace detection
                $isDoorenz = $this->resolveIsDoorenz();

                // Check if dimensions fit digital printing A3+
                $flatSize = \App\Services\BoxCalculatorService::calculateFlatSize($state['type'], $state['dimensions'], 'ap310_dig', $isDoorenz);
                $isDigitalAllowed = $isDoorenz;
                if ($isDigitalAllowed) {
                    if (isset($flatSize['bawah']) && isset($flatSize['atas'])) {
                        $wB = $flatSize['bawah']['w'];
                        $hB = $flatSize['bawah']['h'];
                        $wA = $flatSize['atas']['w'];
                        $hA = $flatSize['atas']['h'];
                        $fitsB = ($wB <= 47.0 && $hB <= 32.0) || ($wB <= 32.0 && $hB <= 47.0);
                        $fitsA = ($wA <= 47.0 && $hA <= 32.0) || ($wA <= 32.0 && $hA <= 47.0);
                        $isDigitalAllowed = $fitsB && $fitsA;
                    } else {
                        $w = $flatSize['w'];
                        $h = $flatSize['h'];
                        $isDigitalAllowed = ($w <= 47.0 && $h <= 32.0) || ($w <= 32.0 && $h <= 47.0);
                    }
                }

                if ($isDoorenz) {
                    if ($isDigitalAllowed) {
                        $text = "📄 *Langkah 4: Pilih Bahan Kertas*\n\n" .
                                "Silakan pilih bahan kertas yang ingin digunakan:\n\n" .
                                "*Offset (Min. 500 pcs):*\n" .
                                "1. Kraft 290gr (Warna coklat, ramah lingkungan, ekonomis)\n" .
                                "2. Ivory 250gr (Warna putih semi-glossy, premium)\n" .
                                "3. Duplex 250gr (Warna putih depan, abu belakang, ekonomis)\n\n" .
                                "*Digital (Min. 50 pcs):*\n" .
                                "4. Art Paper 310 gr (Digital)\n" .
                                "5. Art Paper 260 gr (Digital)\n" .
                                "6. Stiker Chromo (Digital)\n" .
                                "7. Stiker Vinyl (Digital)\n\n" .
                                "*Ketik nomor pilihan Kakak (1 sampai 7):*";
                    } else {
                        $text = "📄 *Langkah 4: Pilih Bahan Kertas*\n\n" .
                                "Silakan pilih bahan kertas yang ingin digunakan:\n\n" .
                                "*Offset (Min. 500 pcs):*\n" .
                                "1. Kraft 290gr (Warna coklat, ramah lingkungan, ekonomis)\n" .
                                "2. Ivory 250gr (Warna putih semi-glossy, premium)\n" .
                                "3. Duplex 250gr (Warna putih depan, abu belakang, ekonomis)\n\n" .
                                "⚠️ _Ukuran box terlalu besar untuk cetak digital A3+ (Maks. 32x47 cm), sehingga hanya tersedia opsi Offset._\n\n" .
                                "*Ketik nomor pilihan Kakak (1 sampai 3):*";
                    }
                } else {
                    $text = "📄 *Langkah 4: Pilih Bahan Kertas*\n\n" .
                            "Silakan pilih bahan kertas yang ingin digunakan:\n\n" .
                            "1. Kraft 290gr (Warna coklat, ramah lingkungan, ekonomis)\n" .
                            "2. Ivory 250gr (Warna putih semi-glossy, premium)\n" .
                            "3. Duplex 250gr (Warna putih depan, abu belakang, ekonomis)\n\n" .
                            "*Ketik nomor pilihan Kakak (1 sampai 3):*";
                }

                $this->dispatchMessage(['text' => $text]);
                break;

            case 'get_material':
                $num = (int)$msg;
                $materials = [
                    1 => 'kraft290_off',
                    2 => 'ivory250_off',
                    3 => 'duplex250_off',
                    4 => 'ap310_dig',
                    5 => 'ap260_dig',
                    6 => 'stiker_chromo_dig',
                    7 => 'stiker_vinyl_dig'
                ];

                $isDoorenz = $this->resolveIsDoorenz();

                if (!$isDoorenz && $num >= 4) {
                    $this->dispatchMessage(['text' => "⚠️ Pilihan bahan tidak valid. Silakan ketik nomor *1 sampai 3* untuk memilih bahan:"]);
                    return;
                }

                // Check if dimensions fit digital printing A3+
                $flatSize = \App\Services\BoxCalculatorService::calculateFlatSize($state['type'], $state['dimensions'], 'ap310_dig', $isDoorenz);
                $isDigitalAllowed = $isDoorenz;
                if ($isDigitalAllowed) {
                    if (isset($flatSize['bawah']) && isset($flatSize['atas'])) {
                        $wB = $flatSize['bawah']['w'];
                        $hB = $flatSize['bawah']['h'];
                        $wA = $flatSize['atas']['w'];
                        $hA = $flatSize['atas']['h'];
                        $fitsB = ($wB <= 47.0 && $hB <= 32.0) || ($wB <= 32.0 && $hB <= 47.0);
                        $fitsA = ($wA <= 47.0 && $hA <= 32.0) || ($wA <= 32.0 && $hA <= 47.0);
                        $isDigitalAllowed = $fitsB && $fitsA;
                    } else {
                        $w = $flatSize['w'];
                        $h = $flatSize['h'];
                        $isDigitalAllowed = ($w <= 47.0 && $h <= 32.0) || ($w <= 32.0 && $h <= 47.0);
                    }
                }

                if ($isDoorenz && !$isDigitalAllowed && $num >= 4) {
                    $this->dispatchMessage(['text' => "⚠️ Pilihan bahan tidak valid. Karena ukuran box terlalu besar untuk cetak digital A3+, silakan ketik nomor *1 sampai 3* untuk memilih bahan Offset:"]);
                    return;
                }

                if (!isset($materials[$num])) {
                    $maxChoice = $isDigitalAllowed ? 7 : 3;
                    $this->dispatchMessage(['text' => "⚠️ Pilihan bahan tidak valid. Ketik angka *1 sampai {$maxChoice}* sesuai menu di atas:"]);
                    return;
                }

                $state['material'] = $materials[$num];

                // Kraft sudah punya laminasi dari bahannya sendiri → langsung tanya jumlah warna (maks 2)
                if ($materials[$num] === 'kraft290_off') {
                    $state['laminasi'] = 'none'; // Kraft tidak pakai laminasi tambahan
                    $state['step'] = 'get_warna';
                    \Cache::put($stateKey, $state, 1800);

                    $this->dispatchMessage(['text' => "🎨 *Langkah 5: Jumlah Warna Cetak*\n\n" .
                                                     "Bahan Kraft 290gr sudah memiliki laminasi bawaan dari bahannya sendiri, sehingga tidak perlu laminasi tambahan. ✅\n\n" .
                                                     "Untuk bahan Kraft, cetak hanya tersedia dalam *1 atau 2 warna* saja (bukan full color).\n\n" .
                                                     "Berapa jumlah warna yang diinginkan Kakak?\n" .
                                                     "1. 1 Warna\n" .
                                                     "2. 2 Warna\n\n" .
                                                     "*Ketik nomor pilihan Kakak (1 atau 2):*"]);
                } else {
                    $state['step'] = 'get_laminasi';
                    \Cache::put($stateKey, $state, 1800);

                    $this->dispatchMessage(['text' => "✨ *Langkah 5: Pilih Laminasi*\n\n" .
                                                     "Apakah kemasan ingin menggunakan laminasi?\n" .
                                                     "1. Tanpa Laminasi\n" .
                                                     "2. Laminasi Glossy (Mengkilap)\n" .
                                                     "3. Laminasi Doff (Matte)\n\n" .
                                                     "*Ketik nomor pilihan Kakak (1, 2, atau 3):*"]);
                }
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

                $state['laminasi'] = $laminations[$num];
                $state['step'] = 'get_warna';
                \Cache::put($stateKey, $state, 1800);

                $this->dispatchMessage(['text' => "🎨 *Langkah 6: Jumlah Warna Cetak*\n\n" .
                                                 "Berapa jumlah warna cetak yang diinginkan Kakak?\n" .
                                                 "1. 1 Warna\n" .
                                                 "2. 2 Warna\n" .
                                                 "3. 3 Warna\n" .
                                                 "4. Full Color (4 Warna CMYK)\n\n" .
                                                 "*Ketik nomor pilihan Kakak (1, 2, 3, atau 4):*"]);
                break;

            case 'get_warna':
                $num = (int)$msg;
                $material = $state['material'];
                $isKraft = ($material === 'kraft290_off');

                // Kraft hanya boleh 1 atau 2 warna
                if ($isKraft) {
                    $warnaOptions = [
                        1 => ['label' => '1 Warna', 'jumlah' => 1],
                        2 => ['label' => '2 Warna', 'jumlah' => 2],
                    ];
                    if (!isset($warnaOptions[$num])) {
                        $this->dispatchMessage(['text' => "⚠️ Untuk bahan Kraft, pilihan hanya *1 atau 2 warna*. Ketik angka *1 atau 2* sesuai menu di atas:"]);
                        return;
                    }
                } else {
                    $warnaOptions = [
                        1 => ['label' => '1 Warna', 'jumlah' => 1],
                        2 => ['label' => '2 Warna', 'jumlah' => 2],
                        3 => ['label' => '3 Warna', 'jumlah' => 3],
                        4 => ['label' => 'Full Color (4 Warna CMYK)', 'jumlah' => 4],
                    ];
                    if (!isset($warnaOptions[$num])) {
                        $this->dispatchMessage(['text' => "⚠️ Pilihan tidak valid. Ketik angka *1, 2, 3, atau 4* sesuai menu di atas:"]);
                        return;
                    }
                }

                $warnaLabel = $warnaOptions[$num]['label'];
                $warnaJumlah = $warnaOptions[$num]['jumlah'];

                $laminasi = $state['laminasi'];
                $type = $state['type'];
                $dimensions = $state['dimensions'];
                $qty = $state['qty'];

                // Run calculation!
                try {
                    // Resolve isDoorenz from owner's workspace
                    $isDoorenzCalc = $this->resolveIsDoorenz();
                    $res = \App\Services\BoxCalculatorService::calculatePrice($type, $dimensions, $qty, $material, $laminasi, $warnaJumlah, $isDoorenzCalc);
                    
                    $prodName = \App\Services\BoxCalculatorService::getProductTypes()[$type];
                    $matName = \App\Services\BoxCalculatorService::getBahanOptions()[$material];
                    $lamName = $isKraft
                        ? 'Laminasi Bawaan Kraft (Built-in)'
                        : \App\Services\BoxCalculatorService::getLaminasiOptions()[$laminasi];
                    
                    $resultText = "📊 *Hasil Perhitungan Harga Box Custom Dooren'z*\n\n" .
                                  "• *Tipe Kemasan:* {$prodName}\n" .
                                  "• *Kertas Bahan:* {$matName}\n" .
                                  "• *Laminasi:* {$lamName}\n" .
                                  "• *Jumlah Warna Cetak:* {$warnaLabel}\n" .
                                  "• *Jumlah Cetak:* " . number_format($qty, 0, ',', '.') . " pcs\n" .
                                  "• *Kertas Plano:* {$res['plano_size']}\n" .
                                  "• *Muat Per Plano:* {$res['items_per_plano']} pcs\n" .
                                  "• *Total Kertas Plano:* " . number_format($res['total_plano'], 0, ',', '.') . " lembar\n\n" .
                                  "💰 *ESTIMASI HARGA JUAL:*\n" .
                                  "• *Harga Satuan:* *Rp " . number_format($res['harga_satuan'], 0, ',', '.') . "/pcs*\n" .
                                  "• *Total Harga:* *Rp " . number_format($res['total_jual'], 0, ',', '.') . "*\n\n" .
                                  "⚠️ *Catatan:* Harga di atas merupakan *harga estimasi saja (tidak mengikat)*.";

                    $listMessage = [
                        'title' => 'Estimasi Harga Box Custom',
                        'text' => $resultText,
                        'footer' => "Dooren'z Percetakan Samarinda",
                        'button_text' => 'Pilih Aksi',
                        'sections' => [
                            [
                                'title' => '─────────────────',
                                'rows' => [
                                    [
                                        'rowId' => 'doorenz_box_custom',
                                        'title' => '⬅️ Hitung Box Kembali',
                                        'description' => 'Kembali ke pilihan jenis Box Custom'
                                    ],
                                    [
                                        'rowId' => 'nav_form_order',
                                        'title' => '📝 Pesan Sekarang',
                                        'description' => 'Isi form pesanan untuk mulai order'
                                    ],
                                    [
                                        'rowId' => 'nav_menu_utama',
                                        'title' => '🏠 Menu Utama',
                                        'description' => 'Kembali ke menu layanan Dooren\'z'
                                    ],
                                    [
                                        'rowId' => 'nav_chat_admin',
                                        'title' => '💬 Chat Dengan Admin',
                                        'description' => 'Hubungi langsung admin untuk bantuan manual'
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $this->dispatchMessage($listMessage, 'number', 'list');
                } catch (\Throwable $th) {
                    \Log::error('Calculator Flow Error: ' . $th->getMessage());
                    $errMessage = $th->getMessage();
                    if (str_contains($errMessage, 'Minimal') || str_contains($errMessage, 'besar') || str_contains($errMessage, 'terlalu')) {
                        $this->dispatchMessage(['text' => "⚠️ *Gagal:* " . $errMessage]);
                    } else {
                        $this->dispatchMessage(['text' => "❌ Terjadi kesalahan saat menghitung harga. Mohon ulangi kembali dengan mengetik *kalkulator*."]);
                    }
                }

                // Clear state
                \Cache::forget($stateKey);
                break;
        }
    }

    private function startDigitalPrintingFlow()
    {
        $text = "🖨️ *Kalkulator Cetak Digital (A3+) Dooren'z*\n\n" .
                "Silakan pilih jenis bahan/kategori:\n" .
                "1. Art Paper/Carton (Flyer, Brosur, Kartu Nama, Voucher, dll)\n" .
                "2. Stiker Chromo (Label Kertas Glossy)\n" .
                "3. Stiker Vinyl (Label Plastik Anti Air)\n" .
                "4. Batal (Keluar dari kalkulator)\n\n" .
                "*Balas dengan mengetik nomor pilihan Kakak (misal: 1 atau 2):*";

        \Cache::put('dig_state_' . $this->chat->id, ['step' => 'choose_product'], 1800);
        $this->dispatchMessage(['text' => $text]);
    }

    private function handleDigitalPrintingFlow(array $state, string $msg)
    {
        $step = $state['step'] ?? '';
        $stateKey = 'dig_state_' . $this->chat->id;

        // Cancel command
        if ($msg === 'batal' || $msg === '/batal' || $msg === 'cancel') {
            \Cache::forget($stateKey);
            $this->dispatchMessage(['text' => "❌ *Kalkulator dibatalkan.* Silakan ketik *cetak digital* kembali jika ingin menghitung ulang."]);
            return;
        }

        switch ($step) {
            case 'choose_product':
                $num = (int)$msg;
                if ($num === 4) {
                    \Cache::forget($stateKey);
                    $this->dispatchMessage(['text' => "❌ *Kalkulator dibatalkan.*"]);
                    return;
                }

                $products = [
                    1 => 'ap',
                    2 => 'chromo',
                    3 => 'vinyl'
                ];

                if (!isset($products[$num])) {
                    $this->dispatchMessage(['text' => "⚠️ Pilihan tidak valid. Silakan balas dengan angka *1 sampai 4* sesuai menu di atas, atau ketik *batal* untuk keluar."]);
                    return;
                }

                $state['type'] = $products[$num];
                $state['step'] = 'get_dimensions';
                \Cache::put($stateKey, $state, 1800);
                
                $this->dispatchMessage(['text' => "📏 *Masukkan Ukuran Cetak* (dalam cm):\n" .
                                                  "Format: *Panjang x Lebar* (contoh: *9x5.5* untuk kartu nama, atau *5x5* untuk stiker/label bulat)."]);
                break;

            case 'get_dimensions':
                if (!preg_match('/(\d+(?:\.\d+)?)\s*[xX*]\s*(\d+(?:\.\d+)?)/', $msg, $matches)) {
                    $this->dispatchMessage(['text' => "⚠️ Format ukuran salah. Contoh format yang benar: *9x5.5* atau *5x5*.\nSilakan ketik ulang ukuran cetak produk Kakak:"]);
                    return;
                }

                $w = (float)$matches[1];
                $h = (float)$matches[2];

                if ($w <= 0 || $h <= 0) {
                    $this->dispatchMessage(['text' => "⚠️ Ukuran panjang dan lebar harus lebih besar dari 0. Silakan ketik ulang ukuran cetak produk Kakak:"]);
                    return;
                }

                $type = $state['type'];
                $bleed = 0.1;
                $printW = 47.0;
                $printH = 32.0;
                $materialName = "Art Paper/Carton";

                if ($type === 'chromo') {
                    $bleed = 0.3;
                    $printW = 46.0;
                    $printH = 31.0;
                    $materialName = "Stiker Chromo";
                } elseif ($type === 'vinyl') {
                    $bleed = 0.3;
                    $printW = 46.0;
                    $printH = 31.0;
                    $materialName = "Stiker Vinyl";
                }

                $w_bleed = $w + 2 * $bleed;
                $h_bleed = $h + 2 * $bleed;

                // Calculate layout fit on digital sheet (printW x printH)
                $pcsPerSheet = \App\Services\BoxCalculatorService::calculateLayoutFit($printW, $printH, $w_bleed, $h_bleed);

                if ($pcsPerSheet <= 0) {
                    $this->dispatchMessage(['text' => "❌ Ukuran produk terlalu besar untuk area cetak digital A3+ (maksimal area cetak {$printH}x{$printW} cm termasuk bleed).\nSilakan ketik *cetak digital* kembali untuk mengulang dengan ukuran yang lebih kecil."]);
                    \Cache::forget($stateKey);
                    return;
                }

                $pcsIn5Sheets = 5 * $pcsPerSheet;

                $state['w'] = $w;
                $state['h'] = $h;
                $state['bleed'] = $bleed;
                $state['printW'] = $printW;
                $state['printH'] = $printH;
                $state['pcsPerSheet'] = $pcsPerSheet;
                $state['pcsIn5Sheets'] = $pcsIn5Sheets;
                $state['materialName'] = $materialName;
                $state['step'] = 'get_qty';
                \Cache::put($stateKey, $state, 1800);

                $promptText = "📏 *Detail Ukuran & Layout Cetak:*\n" .
                             "• *Bahan:* {$materialName}\n" .
                             "• *Ukuran Potong:* {$w} x {$h} cm\n" .
                             "• *Bleed Potong:* {$bleed} cm (Ukuran + Bleed: " . ($w + 2*$bleed) . " x " . ($h + 2*$bleed) . " cm)\n" .
                             "• *Area Cetak Maksimal:* {$printH} x {$printW} cm\n" .
                             "• *Muat per lembar A3+:* {$pcsPerSheet} pcs\n" .
                             "• *Minimal Cetak:* 5 lembar A3+ (Total: *{$pcsIn5Sheets} pcs*)\n\n" .
                             "🔢 *Masukkan Jumlah Cetak* yang Kakak inginkan (dalam pcs, contoh: *100*):\n" .
                             "_(Pemesanan di bawah minimal otomatis disesuaikan ke minimal 5 lembar/{$pcsIn5Sheets} pcs)_";

                $this->dispatchMessage(['text' => $promptText]);
                break;

            case 'get_qty':
                $qty = (int)$msg;
                if ($qty <= 0) {
                    $this->dispatchMessage(['text' => "⚠️ Jumlah cetak harus berupa angka bulat positif (contoh: *100*). Silakan ketik ulang jumlah cetak Kakak:"]);
                    return;
                }

                $type = $state['type'];
                $w = $state['w'];
                $h = $state['h'];
                $bleed = $state['bleed'] ?? 0.1;
                $printW = $state['printW'] ?? 47.0;
                $printH = $state['printH'] ?? 32.0;
                $pcsPerSheet = $state['pcsPerSheet'];
                $materialName = $state['materialName'] ?? "Art Paper/Carton";

                // Run calculation
                try {
                    // Sheets required
                    $sheets = (int)ceil($qty / $pcsPerSheet);
                    $originalSheets = $sheets;
                    $isAdjusted = false;

                    // Minimum order 5 sheets A3+
                    if ($sheets < 5) {
                        $sheets = 5;
                        $isAdjusted = true;
                    }

                    $totalPcs = $sheets * $pcsPerSheet;

                    // Determine sheet price
                    $pricePerSheet = 0;
                    if ($type === 'ap') {
                        if ($sheets < 20) $pricePerSheet = 6000;
                        elseif ($sheets < 50) $pricePerSheet = 4500;
                        else $pricePerSheet = 3500;
                    } elseif ($type === 'chromo') {
                        if ($sheets < 20) $pricePerSheet = 7500;
                        elseif ($sheets < 50) $pricePerSheet = 6000;
                        else $pricePerSheet = 5000;
                    } elseif ($type === 'vinyl') {
                        if ($sheets < 20) $pricePerSheet = 10000;
                        elseif ($sheets < 50) $pricePerSheet = 8000;
                        else $pricePerSheet = 7000;
                    }

                    $totalPrice = $sheets * $pricePerSheet;
                    $unitPrice = $totalPrice / $totalPcs;

                    $resultText = "📊 *Hasil Perhitungan Cetak Digital Dooren'z*\n\n" .
                                  "• *Kategori/Bahan:* {$materialName}\n" .
                                  "• *Ukuran Potong:* {$w} x {$h} cm\n" .
                                  "• *Bleed Potong:* {$bleed} cm\n" .
                                  "• *Muat Per Lembar A3+:* {$pcsPerSheet} pcs\n" .
                                  "• *Kebutuhan Kertas:* {$sheets} lembar A3+\n" .
                                  "• *Total Pcs Didapat:* " . number_format($totalPcs, 0, ',', '.') . " pcs\n\n" .
                                  "💰 *ESTIMASI HARGA JUAL:*\n" .
                                  "• *Harga Satuan:* *Rp " . number_format($unitPrice, 0, ',', '.') . "/pcs*\n" .
                                  "• *Total Harga:* *Rp " . number_format($totalPrice, 0, ',', '.') . "*\n\n" .
                                  "⚠️ *Minimal Cetak:* 5 lembar A3+.";

                    if ($isAdjusted) {
                        $resultText .= "\n\n💡 *Catatan:* Pemesanan Kakak ({$qty} pcs) membutuhkan {$originalSheets} lembar. Karena minimal order adalah 5 lembar A3+, pesanan otomatis disesuaikan menjadi 5 lembar A3+ (mendapatkan {$totalPcs} pcs dengan harga lebih murah/lembar).";
                    }

                    $listMessage = [
                        'title' => 'Estimasi Harga Cetak Digital',
                        'text' => $resultText,
                        'footer' => "Dooren'z Percetakan Samarinda",
                        'button_text' => 'Pilih Aksi',
                        'sections' => [
                            [
                                'title' => '─────────────────',
                                'rows' => [
                                    [
                                        'rowId' => 'doorenz_digital_printing',
                                        'title' => '⬅️ Hitung Digital Kembali',
                                        'description' => 'Kembali ke pilihan bahan Cetak Digital'
                                    ],
                                    [
                                        'rowId' => 'nav_form_order',
                                        'title' => '📝 Pesan Sekarang',
                                        'description' => 'Isi form pesanan untuk mulai order'
                                    ],
                                    [
                                        'rowId' => 'nav_menu_utama',
                                        'title' => '🏠 Menu Utama',
                                        'description' => 'Kembali ke menu layanan Dooren\'z'
                                    ],
                                    [
                                        'rowId' => 'nav_chat_admin',
                                        'title' => '💬 Chat Dengan Admin',
                                        'description' => 'Hubungi langsung admin untuk bantuan manual'
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $this->dispatchMessage($listMessage, 'number', 'list');
                } catch (\Throwable $th) {
                    \Log::error('Digital Printing Flow Error: ' . $th->getMessage());
                    $this->dispatchMessage(['text' => "❌ Terjadi kesalahan saat menghitung harga. Mohon ulangi kembali dengan mengetik *cetak digital*."]);
                }

                // Clear state
                \Cache::forget($stateKey);
                break;
        }
    }

    // ─── Helper: Data preset ukuran per tipe produk ─────────────────────────
    private static function getPresets(string $type): array
    {
        $map = [
            // ── Lunch Box ──────────────────────────────────────────────────
            'lunchBox' => [
                ['label' => 'XS  – 10×10 / T:4.5 cm',      'values' => ['p_atas'=>10,   'l_atas'=>10,   'p_bawah'=>8.5, 'l_bawah'=>8.5, 't'=>4.5, 'tutup'=>2.5], 'desc' => ['P Atas'=>10,   'L Atas'=>10,   'P Bawah'=>8.5, 'L Bawah'=>8.5, 'Tinggi'=>4.5, 'Tutup'=>2.5]],
                ['label' => 'S   – 13×10 / T:4.5 cm',      'values' => ['p_atas'=>13,   'l_atas'=>10,   'p_bawah'=>11,  'l_bawah'=>8,   't'=>4.5, 'tutup'=>2.5], 'desc' => ['P Atas'=>13,   'L Atas'=>10,   'P Bawah'=>11,  'L Bawah'=>8,   'Tinggi'=>4.5, 'Tutup'=>2.5]],
                ['label' => 'M   – 17.5×10.5 / T:5 cm',    'values' => ['p_atas'=>17.5, 'l_atas'=>10.5, 'p_bawah'=>16,  'l_bawah'=>9,   't'=>5,   'tutup'=>2.5], 'desc' => ['P Atas'=>17.5, 'L Atas'=>10.5, 'P Bawah'=>16,  'L Bawah'=>9,   'Tinggi'=>5,   'Tutup'=>2.5]],
                ['label' => 'L   – 20×12 / T:5 cm',        'values' => ['p_atas'=>20,   'l_atas'=>12,   'p_bawah'=>18,  'l_bawah'=>11,  't'=>5,   'tutup'=>2.5], 'desc' => ['P Atas'=>20,   'L Atas'=>12,   'P Bawah'=>18,  'L Bawah'=>11,  'Tinggi'=>5,   'Tutup'=>2.5]],
                ['label' => 'Corndog – 17×6 / T:5 cm',     'values' => ['p_atas'=>17,   'l_atas'=>6,    'p_bawah'=>15,  'l_bawah'=>4,   't'=>5,   'tutup'=>4],   'desc' => ['P Atas'=>17,   'L Atas'=>6,    'P Bawah'=>15,  'L Bawah'=>4,   'Tinggi'=>5,   'Tutup'=>4]],
            ],

            // ── Rice Box ───────────────────────────────────────────────────
            'riceBox' => [
                ['label' => 'S  – 9.5×8 / T:8 cm',         'values' => ['p_atas'=>9.5, 'l_atas'=>8,   'p_bawah'=>8, 'l_bawah'=>6.5, 't'=>8,    'tutup'=>3],   'desc' => ['P Atas'=>9.5, 'L Atas'=>8,   'P Bawah'=>8, 'L Bawah'=>6.5, 'Tinggi'=>8,    'Tutup'=>3]],
                ['label' => 'M  – 9.5×8 / T:10 cm',        'values' => ['p_atas'=>9.5, 'l_atas'=>8,   'p_bawah'=>8, 'l_bawah'=>6.5, 't'=>10,   'tutup'=>3],   'desc' => ['P Atas'=>9.5, 'L Atas'=>8,   'P Bawah'=>8, 'L Bawah'=>6.5, 'Tinggi'=>10,   'Tutup'=>3]],
                ['label' => 'L  – 11×9.5 / T:11.5 cm',     'values' => ['p_atas'=>11,  'l_atas'=>9.5, 'p_bawah'=>9, 'l_bawah'=>8,   't'=>11.5, 'tutup'=>3.5], 'desc' => ['P Atas'=>11,  'L Atas'=>9.5, 'P Bawah'=>9, 'L Bawah'=>8,   'Tinggi'=>11.5, 'Tutup'=>3.5]],
            ],

            // ── Dine In ────────────────────────────────────────────────────
            'dineIn' => [
                ['label' => 'XS – 12×8 / T:2.5 cm',        'values' => ['p'=>12, 'l'=>8,    't'=>2.5],  'desc' => ['Panjang'=>12, 'Lebar'=>8,    'Tinggi'=>2.5]],
                ['label' => 'S  – 11×9 / T:3.5 cm',        'values' => ['p'=>11, 'l'=>9,    't'=>3.5],  'desc' => ['Panjang'=>11, 'Lebar'=>9,    'Tinggi'=>3.5]],
                ['label' => 'M  – 16×9 / T:4 cm',          'values' => ['p'=>16, 'l'=>9,    't'=>4],    'desc' => ['Panjang'=>16, 'Lebar'=>9,    'Tinggi'=>4]],
                ['label' => 'L  – 18×10.5 / T:3.25 cm',    'values' => ['p'=>18, 'l'=>10.5, 't'=>3.25], 'desc' => ['Panjang'=>18, 'Lebar'=>10.5, 'Tinggi'=>3.25]],
                ['label' => 'XL – 18×15.5 / T:5 cm',       'values' => ['p'=>18, 'l'=>15.5, 't'=>5],    'desc' => ['Panjang'=>18, 'Lebar'=>15.5, 'Tinggi'=>5]],
            ],

            // ── Kotak Tutup Terpisah ───────────────────────────────────────
            'kotakTutupTerpisah' => [
                ['label' => 'S  15×15 cm',   'values' => ['p_bawah'=>15, 'l_bawah'=>15, 't_bawah'=>5,   'p_atas'=>15.4, 'l_atas'=>15.4, 't_atas'=>3],   'desc' => ['P Bawah'=>15, 'L Bawah'=>15, 'T Bawah'=>5,   'P Atas'=>15.4, 'L Atas'=>15.4, 'T Atas'=>3]],
                ['label' => 'M  20×20 cm',   'values' => ['p_bawah'=>20, 'l_bawah'=>20, 't_bawah'=>7,   'p_atas'=>20.4, 'l_atas'=>20.4, 't_atas'=>3],   'desc' => ['P Bawah'=>20, 'L Bawah'=>20, 'T Bawah'=>7,   'P Atas'=>20.4, 'L Atas'=>20.4, 'T Atas'=>3]],
                ['label' => 'L  25×25 cm',   'values' => ['p_bawah'=>25, 'l_bawah'=>25, 't_bawah'=>8,   'p_atas'=>25.4, 'l_atas'=>25.4, 't_atas'=>3.5], 'desc' => ['P Bawah'=>25, 'L Bawah'=>25, 'T Bawah'=>8,   'P Atas'=>25.4, 'L Atas'=>25.4, 'T Atas'=>3.5]],
                ['label' => 'Kue 23×23 cm',  'values' => ['p_bawah'=>23, 'l_bawah'=>23, 't_bawah'=>7.5, 'p_atas'=>18,   'l_atas'=>18,   't_atas'=>3],   'desc' => ['P Bawah'=>23, 'L Bawah'=>23, 'T Bawah'=>7.5, 'P Atas'=>18,   'L Atas'=>18,   'T Atas'=>3]],
            ],

            // ── Kotak Sambung (Mailer) – Nama Pelanggan Asli ──────────────
            'kotakSambung' => [
                ['label' => 'Pisang Adina / Ayam Setia – 16×10 / T:5',   'values' => ['p'=>16,   'l'=>10,   't'=>5,   'tutup'=>5],   'desc' => ['Panjang'=>16,   'Lebar'=>10,   'Tinggi'=>5,   'Tutup'=>5]],
                ['label' => 'Dapoer TJ – 18×16 / T:7',                    'values' => ['p'=>18,   'l'=>16,   't'=>7,   'tutup'=>7],   'desc' => ['Panjang'=>18,   'Lebar'=>16,   'Tinggi'=>7,   'Tutup'=>7]],
                ['label' => 'Martabak Leo – 18×12 / T:5',                 'values' => ['p'=>18,   'l'=>12,   't'=>5,   'tutup'=>2.5], 'desc' => ['Panjang'=>18,   'Lebar'=>12,   'Tinggi'=>5,   'Tutup'=>2.5]],
                ['label' => 'Warung Kediri – 18×18 / T:5.5',              'values' => ['p'=>18,   'l'=>18,   't'=>5.5, 'tutup'=>5.5], 'desc' => ['Panjang'=>18,   'Lebar'=>18,   'Tinggi'=>5.5, 'Tutup'=>5.5]],
                ['label' => 'Pawon Rasa – 19.5×19.5 / T:6',              'values' => ['p'=>19.5, 'l'=>19.5, 't'=>6,   'tutup'=>2.5], 'desc' => ['Panjang'=>19.5, 'Lebar'=>19.5, 'Tinggi'=>6,   'Tutup'=>2.5]],
                ['label' => 'Ayam Geprek Dapur Chef – 14×10.5 / T:6.5',  'values' => ['p'=>14,   'l'=>10.5, 't'=>6.5, 'tutup'=>3.3], 'desc' => ['Panjang'=>14,   'Lebar'=>10.5, 'Tinggi'=>6.5, 'Tutup'=>3.3]],
                ['label' => 'Demi Donat Kecil – 18×10 / T:8',            'values' => ['p'=>18,   'l'=>10,   't'=>8,   'tutup'=>8],   'desc' => ['Panjang'=>18,   'Lebar'=>10,   'Tinggi'=>8,   'Tutup'=>8]],
                ['label' => 'Nats Time – 16×12 / T:7',                   'values' => ['p'=>16,   'l'=>12,   't'=>7,   'tutup'=>7],   'desc' => ['Panjang'=>16,   'Lebar'=>12,   'Tinggi'=>7,   'Tutup'=>7]],
                ['label' => 'Demi Donat Besar – 29×20 / T:6',            'values' => ['p'=>29,   'l'=>20,   't'=>6,   'tutup'=>6],   'desc' => ['Panjang'=>29,   'Lebar'=>20,   'Tinggi'=>6,   'Tutup'=>6]],
                ['label' => 'Balok Lumer – 13.5×13.5 / T:4',             'values' => ['p'=>13.5, 'l'=>13.5, 't'=>4,   'tutup'=>4],   'desc' => ['Panjang'=>13.5, 'Lebar'=>13.5, 'Tinggi'=>4,   'Tutup'=>4]],
                ['label' => 'Zaki Donat – 27×18.5 / T:4.5',              'values' => ['p'=>27,   'l'=>18.5, 't'=>4.5, 'tutup'=>4.5], 'desc' => ['Panjang'=>27,   'Lebar'=>18.5, 'Tinggi'=>4.5, 'Tutup'=>4.5]],
            ],

            // ── Straight Tuck End (STE) ────────────────────────────────────
            'straightTuckEnd' => [
                ['label' => 'Bumbu S – 8×4 / T:10',        'values' => ['p'=>8,  'l'=>4, 't'=>10, 'lem'=>1.5], 'desc' => ['Panjang'=>8,  'Lebar'=>4, 'Tinggi'=>10, 'Lem'=>1.5]],
                ['label' => 'Bumbu M – 10×6 / T:14',       'values' => ['p'=>10, 'l'=>6, 't'=>14, 'lem'=>1.5], 'desc' => ['Panjang'=>10, 'Lebar'=>6, 'Tinggi'=>14, 'Lem'=>1.5]],
                ['label' => 'S – 10×5 / T:15',             'values' => ['p'=>10, 'l'=>5, 't'=>15, 'lem'=>1.5], 'desc' => ['Panjang'=>10, 'Lebar'=>5, 'Tinggi'=>15, 'Lem'=>1.5]],
                ['label' => 'M – 12×7 / T:18',             'values' => ['p'=>12, 'l'=>7, 't'=>18, 'lem'=>1.5], 'desc' => ['Panjang'=>12, 'Lebar'=>7, 'Tinggi'=>18, 'Lem'=>1.5]],
                ['label' => 'L – 15×9 / T:22',             'values' => ['p'=>15, 'l'=>9, 't'=>22, 'lem'=>1.5], 'desc' => ['Panjang'=>15, 'Lebar'=>9, 'Tinggi'=>22, 'Lem'=>1.5]],
            ],

            // ── Kebab ──────────────────────────────────────────────────────
            'kebab' => [
                ['label' => 'Mini     – 20×7 cm',           'values' => ['p'=>20, 'l'=>7,  'lem'=>1.5], 'desc' => ['Panjang'=>20, 'Lebar'=>7,  'Lidah Lem'=>1.5]],
                ['label' => 'Standard – 26×9 cm',           'values' => ['p'=>26, 'l'=>9,  'lem'=>1.5], 'desc' => ['Panjang'=>26, 'Lebar'=>9,  'Lidah Lem'=>1.5]],
                ['label' => 'Large    – 30×10 cm',          'values' => ['p'=>30, 'l'=>10, 'lem'=>1.5], 'desc' => ['Panjang'=>30, 'Lebar'=>10, 'Lidah Lem'=>1.5]],
            ],

            // ── Kotak Mug ──────────────────────────────────────────────────
            'kotakMug' => [
                ['label' => 'Mug S  – 8×11 / T:10 cm',     'values' => ['p'=>8,  'l'=>11, 't'=>10, 'lem'=>1.3, 'kunci_bawah'=>8], 'desc' => ['Panjang'=>8,  'Lebar'=>11, 'Tinggi'=>10, 'Lem'=>1.3, 'Kunci Bawah'=>8]],
                ['label' => 'Mug M  – 9×12 / T:11 cm',     'values' => ['p'=>9,  'l'=>12, 't'=>11, 'lem'=>1.3, 'kunci_bawah'=>8], 'desc' => ['Panjang'=>9,  'Lebar'=>12, 'Tinggi'=>11, 'Lem'=>1.3, 'Kunci Bawah'=>8]],
                ['label' => 'Termos – 10×14 / T:12 cm',    'values' => ['p'=>10, 'l'=>14, 't'=>12, 'lem'=>1.3, 'kunci_bawah'=>9], 'desc' => ['Panjang'=>10, 'Lebar'=>14, 'Tinggi'=>12, 'Lem'=>1.3, 'Kunci Bawah'=>9]],
            ],

            // ── Burger ─────────────────────────────────────────────────────
            'burger' => [
                ['label' => 'Slider – 8×8 / T:4 cm',       'values' => ['p'=>8,  'l'=>8,  't_bawah'=>3, 't_krkn'=>4, 't_tutup'=>4], 'desc' => ['Panjang'=>8,  'Lebar'=>8,  'T Bawah'=>3, 'T Karkasan'=>4, 'T Tutup'=>4]],
                ['label' => 'S      – 10×10 / T:4 cm',     'values' => ['p'=>10, 'l'=>10, 't_bawah'=>4, 't_krkn'=>5, 't_tutup'=>5], 'desc' => ['Panjang'=>10, 'Lebar'=>10, 'T Bawah'=>4, 'T Karkasan'=>5, 'T Tutup'=>5]],
                ['label' => 'M      – 12×12 / T:5 cm',     'values' => ['p'=>12, 'l'=>12, 't_bawah'=>5, 't_krkn'=>6, 't_tutup'=>6], 'desc' => ['Panjang'=>12, 'Lebar'=>12, 'T Bawah'=>5, 'T Karkasan'=>6, 'T Tutup'=>6]],
                ['label' => 'L      – 13×13 / T:5 cm',     'values' => ['p'=>13, 'l'=>13, 't_bawah'=>5, 't_krkn'=>7, 't_tutup'=>7], 'desc' => ['Panjang'=>13, 'Lebar'=>13, 'T Bawah'=>5, 'T Karkasan'=>7, 'T Tutup'=>7]],
            ],
            'customFlat' => [
                ['label' => 'A3+ (48.3×32.9 cm)', 'values' => ['p' => 48.3, 'l' => 32.9], 'desc' => ['Panjang' => 48.3, 'Lebar' => 32.9]],
                ['label' => 'A4 (29.7×21 cm)', 'values' => ['p' => 29.7, 'l' => 21.0], 'desc' => ['Panjang' => 29.7, 'Lebar' => 21.0]],
                ['label' => 'A5 (21×14.85 cm)', 'values' => ['p' => 21.0, 'l' => 14.85], 'desc' => ['Panjang' => 21.0, 'Lebar' => 14.85]],
                ['label' => 'Kartu Nama (9×5.5 cm)', 'values' => ['p' => 9.0, 'l' => 5.5], 'desc' => ['Panjang' => 9.0, 'Lebar' => 5.5]],
                ['label' => 'Nota 1/4 (21×10 cm)', 'values' => ['p' => 21.0, 'l' => 10.0], 'desc' => ['Panjang' => 21.0, 'Lebar' => 10.0]],
            ],
        ];

        return $map[$type] ?? [];
    }

    // ─── Helper: Bangun teks menu ukuran (preset + opsi custom) ─────────────
    private static function buildSizeMenu(string $type, string $productName): string
    {
        $presets = self::getPresets($type);
        $total   = count($presets);

        $text  = "📐 *Langkah 2: Pilih Ukuran {$productName}*\n\n";
        $text .= "Berikut ukuran referensi yang tersedia. Pilih yang sesuai atau masukkan ukuran sendiri:\n\n";

        foreach ($presets as $i => $p) {
            $text .= ($i + 1) . ". {$p['label']}\n";
        }

        $text .= ($total + 1) . ". ✏️ *Masukkan Ukuran Sendiri*\n\n";
        $text .= "*Ketik nomor pilihan Kakak (1–" . ($total + 1) . "):*";

        return $text;
    }

    // ─── Helper: Prompt input ukuran manual ──────────────────────────────────
    private static function buildDimPrompt(string $type, string $productName): string
    {
        return match ($type) {
            'lunchBox', 'riceBox' =>
                "📏 *Masukkan Ukuran {$productName} (cm)*\n\n" .
                "Format: *P Atas, L Atas, P Bawah, L Bawah, Tinggi, Tutup*\n" .
                "Contoh: *18,10.5,16,8.5,5,2.5*",
            'dineIn' =>
                "📏 *Masukkan Ukuran {$productName} (cm)*\n\n" .
                "Format: *Panjang, Lebar, Tinggi*\n" .
                "Contoh: *18,10,4*",
            'kotakTutupTerpisah' =>
                "📏 *Masukkan Ukuran {$productName} (cm)*\n\n" .
                "Format: *P Bawah, L Bawah, T Bawah, P Atas, L Atas, T Atas*\n" .
                "Contoh: *23,23,7.5,18,18,3*",
            'kotakSambung' =>
                "📏 *Masukkan Ukuran {$productName} (cm)*\n\n" .
                "Format: *Panjang, Lebar, Tinggi, Tutup*\n" .
                "Contoh: *14,10.5,6.5,3.5*",
            'straightTuckEnd' =>
                "📏 *Masukkan Ukuran {$productName} (cm)*\n\n" .
                "Format: *Panjang, Lebar, Tinggi, Kuping Lem*\n" .
                "Contoh: *10,5,15,1.5*",
            'kebab' =>
                "📏 *Masukkan Ukuran Kebab (cm)*\n\n" .
                "Format: *Panjang, Lebar, Lidah Lem*\n" .
                "Contoh: *26,9,1.5*",
            'kotakMug' =>
                "📏 *Masukkan Ukuran Kotak Mug (cm)*\n\n" .
                "Format: *Panjang, Lebar, Tinggi, Lidah Lem, Kunci Bawah*\n" .
                "Contoh: *8,11,10,1.3,8*",
            'burger' =>
                "📏 *Masukkan Ukuran Kotak Burger (cm)*\n\n" .
                "Format: *Panjang, Lebar, T Bawah, T Karkasan, T Tutup*\n" .
                "Contoh: *10,10,4,5,5*",
            'customFlat' =>
                "📏 *Masukkan Ukuran {$productName} (cm)*\n\n" .
                "Format: *Panjang, Lebar*\n" .
                "Contoh: *21,15*",
            default =>
                "📏 *Masukkan Ukuran {$productName}*\n\nMasukkan dimensi dipisah koma (dalam cm):",
        };
    }

    // ─── Helper: Resolve isDoorenz ────────────────────────────────────────────
    private ?bool $isDoorenzCache = null;

    private function resolveIsDoorenz(): bool
    {
        if ($this->isDoorenzCache !== null) {
            return $this->isDoorenzCache;
        }

        $owner     = $this->platform->owner;
        $workspace = $owner?->getCurrentWorkspace();
        $name      = strtolower($workspace?->name ?? '');

        $this->isDoorenzCache = str_contains($name, 'doorenz') || str_contains($name, "dooren'z");

        return $this->isDoorenzCache;
    }

    public function isOutOfOperationalHours(): bool
    {
        $now = now()->timezone('Asia/Makassar');
        $dateStr = $now->format('Y-m-d');
        $dayOfWeek = $now->dayOfWeek; // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
        $currentTime = $now->format('H:i');

        // 1. Check if Sunday (Minggu)
        if ($dayOfWeek === 0) {
            return true;
        }

        // 2. Check if Public Holiday
        $holidays = [
            '2026-01-01', // Tahun Baru
            '2026-01-16', // Isra Mikraj
            '2026-02-17', // Tahun Baru Imlek
            '2026-03-19', // Hari Suci Nyepi
            '2026-03-21', // Idul Fitri
            '2026-03-22', // Idul Fitri
            '2026-04-03', // Wafat Yesus Kristus
            '2026-04-05', // Paskah
            '2026-05-01', // Hari Buruh
            '2026-05-14', // Kenaikan Yesus Kristus
            '2026-05-27', // Idul Adha
            '2026-05-31', // Waisak
            '2026-06-01', // Hari Lahir Pancasila
            '2026-06-16', // Tahun Baru Islam
            '2026-08-17', // Kemerdekaan RI
            '2026-08-25', // Maulid Nabi
            '2026-12-25', // Hari Raya Natal
        ];

        if (in_array($dateStr, $holidays)) {
            return true;
        }

        // 3. Check operational hours
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            if ($currentTime < '09:00' || $currentTime > '18:00') {
                return true;
            }
        }
        if ($dayOfWeek === 6) {
            if ($currentTime < '09:00' || $currentTime > '17:00') {
                return true;
            }
        }

        return false;
    }

    public function handleOutOfHoursReply()
    {
        $cacheKey = 'ooo_sent_web_' . $this->platform->id . '_' . $this->chat->id;
        if (\Cache::has($cacheKey)) {
            return;
        }

        // Get Out of Office message text
        $oooText = "";
        $oooTemplateSetting = $this->platform->getMeta('ooo_message_template', '');
        if (preg_match('/\[template:(\d+)\]/', trim($oooTemplateSetting), $matches)) {
            $templateId = $matches[1];
            $template = \Illuminate\Support\Facades\DB::table('templates')->where('id', $templateId)->first();
            if ($template) {
                $meta = json_decode($template->meta, true);
                $oooText = $meta['text'] ?? '';
            }
        } else {
            $oooText = $oooTemplateSetting;
        }

        if (empty($oooText)) {
            return;
        }

        // Try to load the Welcome Menu template (Template 20) to combine with the OOO message
        $welcomeTemplateSetting = $this->platform->getMeta('welcome_message_template', '');
        $welcomeTemplate = null;
        if (preg_match('/\[template:(\d+)\]/', trim($welcomeTemplateSetting), $matches)) {
            $templateId = $matches[1];
            $welcomeTemplate = \Illuminate\Support\Facades\DB::table('templates')->where('id', $templateId)->first();
        }

        if ($welcomeTemplate && $welcomeTemplate->type === 'list') {
            $listMeta = json_decode($welcomeTemplate->meta, true);
            $listMeta['text'] = $oooText; // Replace Welcome Menu text with OOO info text
            $this->dispatchMessage(
                $listMeta,
                'number',
                'list'
            );
        } else {
            // Fallback: send text OOO message
            $this->dispatchMessage(
                [
                    'text' => $oooText,
                ],
                'number',
                'text'
            );
        }

        \Cache::put($cacheKey, true, now()->addDay());
    }
}
