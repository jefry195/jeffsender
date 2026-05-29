<?php

namespace Modules\WhatsappWeb\App\Jobs;

use App\Models\Platform;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class HandleIncomingMessageJob implements ShouldQueue
{
    use Dispatchable;

    public ?Platform $platform;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $payload)
    {
        $this->platform = Platform::query()->where('uuid', $this->payload['sessionId'])->first();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! $this->platform) {
            logOnDebug('WhatsappWeb: Platform not found for sessionId '.$this->payload['sessionId']);

            return;
        }

        $messages = data_get($this->payload, 'data.messages', []);

        foreach ($messages as $message) {
            $this->handleMessage($message);
        }

    }

    public function handleMessage(array $message)
    {
        $remoteJid = data_get($message, 'key.remoteJid', '');
        $remoteJidAlt = data_get($message, 'key.remoteJidAlt', '');
        if (str($remoteJid)->contains('@lid') && str($remoteJidAlt)->contains('@s.whatsapp.net')) {
            data_set($message, 'key.remoteJid', $remoteJidAlt);
            $remoteJid = $remoteJidAlt;
        }

        // Skip messages that are reactions, protocol messages, or polls (not meaningful for auto-reply)
        $messageKeys = array_keys(data_get($message, 'message', []));
        $skipTypes = ['reactionMessage', 'protocolMessage', 'pollCreationMessage', 'pollUpdateMessage', 'ephemeralMessage'];
        foreach ($skipTypes as $skipType) {
            if (in_array($skipType, $messageKeys)) {
                logOnDebug('WhatsappWeb: Skipping message type: ' . $skipType);
                return;
            }
        }

        try {
            $this->createPlatformLog($message);
        } catch (\Throwable $th) {
            logOnDebug('WhatsappWeb: Failed to create platform log: ' . $th->getMessage());
        }

        $this->handleAutoReply($message);
    }

    private function createPlatformLog(array $message)
    {
        $messageText = $this->extractMessageText($message);
        $fromMe = data_get($message, 'key.fromMe', false);

        return $this->platform->logs()->create([
            'module' => 'whatsapp-web',
            'owner_id' => $this->platform->owner_id,
            'direction' => $fromMe ? 'out' : 'in',
            'message_type' => $this->guessMsgType($message),
            'message_text' => $messageText,
            'meta' => $this->payload,
        ]);

    }

    private function handleAutoReply(array $message)
    {
        $fromMe = data_get($message, 'key.fromMe', false);

        // Only trigger auto-reply for incoming text messages
        $messageText = $this->extractMessageText($message);

        \Log::debug('WhatsappWeb: Analyzing auto-reply', [
            'jid' => data_get($message, 'key.remoteJid'),
            'fromMe' => $fromMe,
            'text' => $messageText,
            'types' => array_keys(data_get($message, 'message', []))
        ]);

        if ($fromMe == false && $messageText) {
            // Optimization: Process autoreply directly if possible to reduce queue latency
            $this->processAutoReplyDirectly($messageText, data_get($message, 'key.remoteJid'));
        } elseif ($fromMe == false) {
            logOnDebug('WhatsappWeb: no text found in message for auto-reply', [
                'jid' => data_get($message, 'key.remoteJid'),
                'types' => array_keys(data_get($message, 'message', [])),
            ]);
        }

    }

    private function processAutoReplyDirectly(string $messageText, string $jid)
    {
        try {
            // We use the same logic as HandleAutoReplyJob but without the extra queue step
            $chat = \App\Models\Chat::query()
                ->where('sessionId', $this->platform->uuid)
                ->where('id', $jid)
                ->first();

            if (! $chat) {
                // Fallback: try to find by phone number (removing domain)
                $phoneNumber = str($jid)->before('@')->before(':')->toString();
                $chat = \App\Models\Chat::query()
                    ->where('sessionId', $this->platform->uuid)
                    ->where('id', 'like', $phoneNumber . '%')
                    ->first();
            }

            if (! $chat) {
                logOnDebug('WhatsappWeb: Chat not found for JID ' . $jid);
                return;
            }

            $autoReplyService = new \Modules\WhatsappWeb\App\Services\AutoReplyService(
                $messageText,
                $this->platform,
                $chat
            );
            $autoReplyService->handleAutoReply();

        } catch (\Throwable $th) {
            logOnDebug('WhatsappWeb: Direct AutoReply failed, falling back to job: ' . $th->getMessage());
            HandleAutoReplyJob::dispatch($messageText, $this->platform, $jid);
        }
    }

    /**
     * Extract text content from a message, handling multiple message types.
     */
    private function extractMessageText(array $message): ?string
    {
        // Direct text
        $text = data_get($message, 'message.conversation');
        if ($text) return $text;

        // Extended text (with quote/link)
        $text = data_get($message, 'message.extendedTextMessage.text');
        if ($text) return $text;

        // Edited message
        $text = data_get($message, 'message.editedMessage.message.protocolMessage.editedMessage.conversation');
        if ($text) return $text;

        // Image/video caption
        $text = data_get($message, 'message.imageMessage.caption');
        if ($text) return $text;

        $text = data_get($message, 'message.videoMessage.caption');
        if ($text) return $text;

        $text = data_get($message, 'message.documentMessage.caption');
        if ($text) return $text;

        return null;
    }

    private function guessMsgType(array $message): string
    {
        $type = 'other';

        if (isset($message['message']['conversation']) || isset($message['message']['extendedTextMessage'])) {
            $type = 'text';
        }
        if (isset($message['message']['audioMessage'])) {
            $type = 'audio';
        }
        if (isset($message['message']['imageMessage'])) {
            $type = 'image';
        }
        if (isset($message['message']['videoMessage'])) {
            $type = 'video';
        }
        if (isset($message['message']['stickerMessage'])) {
            $type = 'sticker';
        }
        if (isset($message['message']['documentMessage'])) {
            $type = 'document';
        }
        if (isset($message['message']['pollCreationMessage'])) {
            $type = 'poll';
        }
        if (isset($message['message']['locationMessage'])) {
            $type = 'location';
        }
        if (isset($message['message']['reactionMessage'])) {
            $type = 'reaction';
        }

        return $type;

    }
}
