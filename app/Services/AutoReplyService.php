<?php

namespace App\Services;

use App\Helpers\ModuleServiceResolver;
use App\Jobs\SendMessageJob;
use App\Models\AutoReply;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Platform;
use App\Models\User;
use Modules\Whatsapp\App\Services\TemplateService;

class AutoReplyService
{
    public string $activeModule;

    public Conversation $conversation;

    public Platform $platform;

    public User $owner;

    public ?string $messageText;

    public function __construct(
        public Message $message
    ) {
        $this->activeModule = $message->module;
        $this->conversation = $message->conversation;
        $this->platform = $message->platform;
        $this->owner = $this->platform->owner;
        $this->messageText = str($this->message->getText())->trim()->lower()->toString();
    }

    public static function for(Message $message): static
    {
        return new self($message);
    }

    public static function getAutoReplyServices(string $activeModule): array
    {
        $autoReplyServices = self::defaultServices();

        $modulesPath = base_path('modules');
        $moduleFolders = scandir($modulesPath);

        foreach ($moduleFolders as $moduleFolder) {
            if ($moduleFolder === '.' || $moduleFolder === '..') {
                continue;
            }

            $moduleJsonPath = $modulesPath.DIRECTORY_SEPARATOR.$moduleFolder.DIRECTORY_SEPARATOR.'module.json';

            if (file_exists($moduleJsonPath)) {
                $moduleJsonContent = file_get_contents($moduleJsonPath);
                $moduleData = json_decode($moduleJsonContent, true);

                if (isset($moduleData['service_type']) && $moduleData['service_type'] === 'auto_reply') {
                    $moduleMessageService = ModuleServiceResolver::resolve($moduleData['name'], 'DatasetService');
                    $dataSets = $moduleMessageService->getDatasets($activeModule);
                    $autoReplyServices[] = self::createService(
                        str($moduleData['name'])->headline()->toString(),
                        $moduleFolder,
                        true,
                        $dataSets
                    );
                }
            }
        }

        return $autoReplyServices;
    }

    public static function defaultServices(): array
    {
        return [
            self::createService('Module Default', 'default', false, []),
        ];
    }

    public static function createService(string $title, string $module, bool $has_datasets = false, array $datasets = [])
    {
        return [
            'title' => $title,
            'module' => $module,
            'has_datasets' => $has_datasets,
            'datasets' => $datasets,
        ];
    }

    public function sendAutoReply()
    {
        if (! $this->isAutoReplyEnabled()) {
            logOnDebug('auto reply not enabled');
            return;
        }

        $autoReplyMethod = $this->platform->getMeta('auto_reply_method');

        if ($autoReplyMethod === 'default') {
            return $this->handleDefaultReply();
        } else {
            // For custom modules, send welcome message if enabled
            $sendWelcomeMessageSetting = $this->platform->getMeta('send_welcome_message', false);
            if ($sendWelcomeMessageSetting) {
                $this->sendWelcomeMessageDirect();
            }
            return $this->handleModuleAutoReply();
        }
    }

    public function handleModuleAutoReply()
    {
        $moduleName = $this->platform->getMeta('auto_reply_method');
        $datasetId = $this->platform->getMeta('auto_reply_dataset', 0);
        $messageText = $this->messageText;

        if (! $moduleName || ! $datasetId || ! $messageText) {
            logOnDebug('auto reply module or dataset or message not found');

            return;
        }

        $replyService = ModuleServiceResolver::resolveReplyService($moduleName);

        $replyService->using($datasetId, $messageText, [
            'module' => $this->activeModule,
            'conversation_id' => $this->conversation->id,
            'owner_id' => $this->message->owner_id,
            'user_id' => $this->message->owner_id,
            'platform_uuid' => $this->platform->uuid,
        ])->process();

        $messages = $replyService->getMessages();

        if (! $messages) {
            return;
        }

        foreach ($messages as $message) {

            if (! $message['type'] || ! $message['body']) {
                logOnDebug('message type or body not found', [
                    'message' => $message,
                ]);

                continue;
            }

            $this->dispatchMessage([
                'module' => $this->activeModule,
                'platform_id' => $this->platform->id,
                'conversation_id' => $this->conversation->id,
                'owner_id' => $this->owner->id,
                'customer_id' => $this->conversation->customer_id,
                'uuid' => null,
                'direction' => 'out',
                'type' => $message['type'],
                'body' => $message['body'],
                'status' => 'pending',
            ]);
        }
    }

    public function sendWelcomeMessageDirect(): bool
    {
        $platform = $this->platform;
        $welcomeMessageTemplate = $platform->getMeta('welcome_message_template', '');
        if (! $welcomeMessageTemplate) {
            return false;
        }

        $messageType = 'text';
        $messageBody = null;

        if (preg_match('/\[template:(\d+)\]/', trim($welcomeMessageTemplate), $matches)) {
            $templateId = (int)$matches[1];
            $template = \App\Models\Template::find($templateId);
            if ($template) {
                $templateService = new TemplateService($template, $this->conversation, $this->conversation->customer);
                $messageBody = $templateService->generateMessageBody();
                $messageType = $template->type;
            }
        }

        if (! $messageBody) {
            $messageBody = ModuleServiceResolver::resolveComposerService($platform->module)->textMessage($welcomeMessageTemplate);
        }

        if (empty($messageBody)) {
            logOnDebug('message body not found', [
                'messageBody' => $messageBody,
            ]);

            return false;
        }

        $this->dispatchMessage([
            'module' => $this->activeModule,
            'platform_id' => $this->platform->id,
            'conversation_id' => $this->conversation->id,
            'owner_id' => $this->owner->id,
            'customer_id' => $this->conversation->customer_id,

            'uuid' => null,
            'direction' => 'out',
            'type' => $messageType,
            'body' => $messageBody,
            'status' => 'pending',
            'meta' => [
                'is_welcome_message' => true
            ],
        ]);

        return true;
    }

    public function sendWelcomeMessage(): static
    {
        $platform = $this->platform;
        if (! $platform) {
            return $this;
        }

        $send_welcome_message = $platform->getMeta('send_welcome_message', false);
        if (! $send_welcome_message) {
            return $this;
        }

        $this->sendWelcomeMessageDirect();

        return $this;
    }

    private function handleDefaultReply()
    {
        $prompt = $this->messageText;
        if (! $prompt) {
            logOnDebug('no prompt found', [
                'prompt' => $prompt,
            ]);

            return;
        }

        $bestMatch = $this->findBestMatch($prompt);

        // Retrieve welcome template info
        $welcomeTemplateId = null;
        $welcomeTemplateSetting = $this->platform->getMeta('welcome_message_template', '');
        if (preg_match('/\[template:(\d+)\]/', trim($welcomeTemplateSetting), $matches)) {
            $welcomeTemplateId = (int)$matches[1];
        }

        if ($bestMatch) {
            // Check if matched auto-reply is same as welcome message template
            $isSameAsWelcome = false;
            if ($welcomeTemplateId && $bestMatch->message_type === 'template' && $bestMatch->template_id == $welcomeTemplateId) {
                $isSameAsWelcome = true;
            } elseif ($welcomeTemplateSetting && $bestMatch->message_type === 'text' && trim($bestMatch->message_template) === trim($welcomeTemplateSetting)) {
                $isSameAsWelcome = true;
            }

            if ($isSameAsWelcome) {
                logOnDebug('AutoReply: Sending welcome message template via matched auto-reply');
                $this->sendWelcomeMessageDirect();
                return;
            }

            // It is a DIFFERENT template/message. Send it directly without rate-limiting.
            $messageType = $bestMatch->message_type;
            $messageComposer = ModuleServiceResolver::resolveComposerService($this->platform->module);
            $messageBody = null;

            if ($messageType == 'text') {
                $messageBody = $messageComposer->textMessage($bestMatch->message_template);
            } elseif ($messageType == 'template') {
                $templateService = new TemplateService($bestMatch->template, $this->conversation, $this->conversation->customer);
                $messageBody = $templateService->generateMessageBody();
            }

            if (! $messageBody) {
                return;
            }

            if ($messageType == 'template') {
                $messageType = $bestMatch->template->type;
            }

            $this->dispatchMessage([
                'module' => $this->activeModule,
                'platform_id' => $this->platform->id,
                'conversation_id' => $this->conversation->id,
                'owner_id' => $this->owner->id,
                'customer_id' => $this->conversation->customer_id,

                'uuid' => null,
                'direction' => 'out',
                'type' => $messageType,
                'body' => $messageBody,
                'status' => 'pending',
            ]);
            return;
        }

        // If no best match, check if Out of Office is enabled and active
        if ($this->platform->isOooMessageEnabled() && $this->isOutOfOperationalHours()) {
            $this->handleOutOfHoursReply();
            return;
        }

        // If no best match, check if welcome message should be sent
        $sendWelcomeMessage = $this->platform->getMeta('send_welcome_message', false);
        if ($sendWelcomeMessage) {
            logOnDebug('AutoReply: No match, sending welcome message');
            $this->sendWelcomeMessageDirect();
        }

    }

    private function dispatchMessage(array $message)
    {
        dispatch(new SendMessageJob($message));
    }

    private function isAutoReplyEnabled(): bool
    {
        return $this->platform &&
            $this->platform->isAutoReplyEnabled() &&
            $this->conversation->isAutoReplyEnabled();
    }

    private function findBestMatch(string $searchQuery): ?AutoReply
    {
        $searchTerms = explode(' ', $searchQuery);
        // Also include the full query as a term so exact-phrase / single-word keywords match
        $searchTerms[] = $searchQuery;
        $searchTerms = array_unique($searchTerms);

        $potentialMatches = $this->owner
            ->autoReplies()
            ->active()
            ->module($this->activeModule)
            ->matchKeywords($searchTerms)
            ->get();

        $bestMatch = null;
        $maxMatchCount = 0;

        foreach ($potentialMatches as $potentialMatch) {
            // Normalize stored keywords to lowercase before comparing
            $normalizedKeywords = array_map('strtolower', $potentialMatch->keywords);
            $matchCount = count(array_intersect($searchTerms, $normalizedKeywords));

            if ($matchCount > $maxMatchCount) {
                $maxMatchCount = $matchCount;
                $bestMatch = $potentialMatch;
            }
        }

        return $bestMatch;
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
        $cacheKey = 'ooo_sent_' . $this->platform->id . '_' . $this->conversation->id;
        if (\Cache::has($cacheKey)) {
            return;
        }

        $textMessage = $this->platform->getOooMessageTemplate();
        if (empty($textMessage)) {
            return;
        }

        $oooText = "";
        if (preg_match('/\[template:(\d+)\]/', trim($textMessage), $matches)) {
            $templateId = (int)$matches[1];
            $template = \App\Models\Template::find($templateId);
            if ($template) {
                $templateService = new TemplateService($template, $this->conversation, $this->conversation->customer);
                $meta = $templateService->generateMessageBody();
                $oooText = $meta['text'] ?? '';
            }
        } else {
            $oooText = $textMessage;
        }

        if (empty($oooText)) {
            return;
        }

        $welcomeTemplateSetting = $this->platform->getWelcomeMessageTemplate();
        $welcomeTemplate = null;
        if (preg_match('/\[template:(\d+)\]/', trim($welcomeTemplateSetting), $matches)) {
            $templateId = (int)$matches[1];
            $welcomeTemplate = \App\Models\Template::find($templateId);
        }

        $messageType = 'text';
        $messageBody = null;

        if ($welcomeTemplate && $welcomeTemplate->type === 'list') {
            $templateService = new TemplateService($welcomeTemplate, $this->conversation, $this->conversation->customer);
            $messageBody = $templateService->generateMessageBody();
            $messageBody['text'] = $oooText;
            $messageType = 'list';
        } else {
            $messageComposer = ModuleServiceResolver::resolveComposerService($this->platform->module);
            $messageBody = $messageComposer->textMessage($oooText);
        }

        if (empty($messageBody)) {
            return;
        }

        $this->dispatchMessage([
            'module' => $this->activeModule,
            'platform_id' => $this->platform->id,
            'conversation_id' => $this->conversation->id,
            'owner_id' => $this->owner->id,
            'customer_id' => $this->conversation->customer_id,

            'uuid' => null,
            'direction' => 'out',
            'type' => $messageType,
            'body' => $messageBody,
            'status' => 'pending',
        ]);

        \Cache::put($cacheKey, true, now()->addDay());
    }
}
