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
        if ($this->isAutoReplyEnabled()) {
            return;
        }

        $autoReplyMethod = $this->platform->getMeta('auto_reply_method');

        return match ($autoReplyMethod) {
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

    public function sendWelcomeMessage(): static
    {
        // return if platform not exists
        $platform = $this->platform;
        if (! $platform) {
            logOnDebug('platform not found', [
                'platform' => $platform,
            ]);

            return $this;
        }

        // return if auto reply not enabled
        $send_welcome_message = $platform->getMeta('send_welcome_message', false);
        if (! $send_welcome_message) {
            logOnDebug('auto reply not enabled', [
                'meta' => $platform->meta,
            ]);

            return $this;
        }

        // return if auto reply not enabled or message template not found
        $welcomeMessageTemplate = $platform->getMeta('welcome_message_template', '');
        if (! $welcomeMessageTemplate) {
            logOnDebug('welcome message template not found', [
                'meta' => $platform->meta,
            ]);

            return $this;
        }

        // return if last message is not passed 24 hours
        $lastMsgSend = $this->conversation->getMeta('last_message_at');
        if ($lastMsgSend && now()->diffInHours($lastMsgSend) < 24) {
            logOnDebug('last message is not passed 24 hours', [
                'lastMsgSend' => $lastMsgSend,
                'diffInHours' => now()->diffInHours($lastMsgSend, true),
            ]);

            return $this;
        }

        $messageBody = ModuleServiceResolver::resolveComposerService($platform->module)->textMessage($welcomeMessageTemplate);

        if (empty($messageBody)) {
            logOnDebug('message body not found', [
                'messageBody' => $messageBody,
            ]);

            return $this;
        }

        $this->dispatchMessage([
            'module' => $this->activeModule,
            'platform_id' => $this->platform->id,
            'conversation_id' => $this->conversation->id,
            'owner_id' => $this->owner->id,
            'customer_id' => $this->conversation->customer_id,

            'uuid' => null,
            'direction' => 'out',
            'type' => 'text',
            'body' => $messageBody,
            'status' => 'pending',
        ]);

        return $this;
    }

    private function handleDefaultReply()
    {
        // return if prompt not found
        $prompt = $this->messageText;
        if (! $prompt) {
            logOnDebug('no prompt found', [
                'prompt' => $prompt,
            ]);

            return;
        }

        // return if best match not found
        $bestMatch = $this->findBestMatch($prompt);
        if (! $bestMatch) {
            logOnDebug('no best match found', [
                'prompt' => $prompt,
            ]);

            return;
        }

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

    }

    private function dispatchMessage(array $message)
    {
        dispatch(new SendMessageJob($message));
    }

    private function isAutoReplyEnabled(): bool
    {
        return ! $this->platform ||
            ! $this->platform->isAutoReplyEnabled() ||
            ! $this->conversation->isAutoReplyEnabled();
    }

    private function findBestMatch(string $searchQuery): ?AutoReply
    {
        $searchTerms = explode(' ', $searchQuery);

        $potentialMatches = $this->owner
            ->autoReplies()
            ->active()
            ->module($this->activeModule)
            ->matchKeywords($searchTerms)
            ->get();

        $bestMatch = null;
        $maxMatchCount = 0;

        foreach ($potentialMatches as $potentialMatch) {
            $matchCount = count(array_intersect($searchTerms, $potentialMatch->keywords));

            if ($matchCount > $maxMatchCount) {
                $maxMatchCount = $matchCount;
                $bestMatch = $potentialMatch;
            }
        }

        return $bestMatch;
    }
}
