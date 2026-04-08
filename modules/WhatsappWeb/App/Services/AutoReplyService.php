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

        // todo: remove after testing
        // if ($this->sendWelcomeMessage()) {
        //     logOnDebug('welcome message sent');

        //     return;
        // }

        if (! $this->isAutoReplyEnabled()) {
            logOnDebug('auto reply not enabled');

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
        }

        $is24hPassed = now()->diffInHours($lastMessageSendAt, true) > 24;

        if (! $is24hPassed) {
            return false;
        }

        $autoReplyEnabled = $platform->getMeta('send_auto_reply', false);
        $welcomeMessageTemplate = $platform->getMeta('welcome_message_template', '');

        if (! $autoReplyEnabled || ! $welcomeMessageTemplate) {
            return false;
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
        $potentialMatches = AutoReply::query()
            ->where('module', 'whatsapp-web')
            ->where('owner_id', $this->platform->owner_id)
            ->matchKeywords($searchTerms)
            ->get();

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
        return str_replace(
            '{name}',
            $this->chat?->name ?? '{name}',
            $text
        );
    }
}
