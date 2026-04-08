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
        }

        $this->createPlatformLog($message);
        $this->handleAutoReply($message);
    }

    private function createPlatformLog(array $message)
    {
        $messageText = data_get($message, 'message.conversation') ?: data_get($message, 'message.extendedTextMessage.text');
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
        $messageText = data_get($message, 'message.conversation') ?: data_get($message, 'message.extendedTextMessage.text');
        
        if ($fromMe == false && $messageText) {
            HandleAutoReplyJob::dispatch($messageText, $this->platform, data_get($message, 'key.remoteJid'));
        }

    }

    private function guessMsgType(array $message): string
    {
        $type = 'other';

        if (isset($message['message']['conversation'])) {
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

        return $type;

    }
}
