<?php

namespace Modules\Whatsapp\App\Services;

use App\Contracts\MessageServiceContract;
use App\Models\Message;
use App\Models\PlatformLog;
use App\Traits\Uploader;
use Illuminate\Support\Facades\DB;

class MessageService implements MessageServiceContract
{
    use Uploader;

    protected array $shortCodes = [];

    public function __construct(public Message $message)
    {
        $customer = $message->customer;

        if ($customer) {
            $this->shortCodes = [
                '{name}' => $customer->name,
                '{phone}' => $customer->uuid,
            ];
        }

        // modify the message body for ai reply message structure
        if (is_string($message->getBody('text'))) {
            $this->message->body = [
                'body' => $message->getBody('text'),
            ];
        }
    }

    /**
     * Send a message to a customer
     * and store the message, platform log in the database.
     * then return the saved message
     */
    public function send(): Message
    {
        $newMessage = $this->message;

        $payload = $this
            ->replaceShortcode()
            ->generatePayload();

        $platform = $this->message->platform;

        if (! $platform) {
            logOnDebug('Platform not found');
        }

        $accessToken = $platform->access_token;
        $phoneNumberId = $platform->uuid;

        $res = WhatsappClient::make(
            $accessToken,
            $phoneNumberId
        )->postMessage($payload)->throw(function ($res) {
            throw new \Exception($res->json('error.message'));
        });

        $uuid = $res->json('messages.0.id');

        if (! $uuid) {
            throw new \Exception('Failed to send message: '.$res->json('error.message'));
        }

        // Set the uuid of the message to the id returned by whatsapp
        $newMessage->uuid = $uuid;

        DB::beginTransaction();

        // Save the message to the database
        $newMessage->save();

        $newMessage->conversation->touch();

        // Log the message
        PlatformLog::create([
            'module' => 'whatsapp',
            'owner_id' => $newMessage->owner_id,
            'platform_id' => $newMessage->platform_id,
            'customer_id' => $newMessage->customer_id,
            'direction' => 'out',
            'message_type' => $newMessage->type,
            'message_text' => $newMessage->getText(),
            'meta' => $res->json(),
        ]);

        DB::commit();

        return $newMessage;
    }

    public function generatePayload(): array
    {
        $message = $this->message;
        $messageType = $message->type;
        $recipientId = $message->customer->uuid;
        $messageBody = $message->body;

        if ($messageType == 'interactive') {
            unset($messageBody['schedule_timezone'], $messageBody['schedule_timestamp']);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipientId,
            'type' => $messageType,
            $messageType => $messageBody,
        ];

        if (isset($message->meta['context']['id'])) {
            $payload['context']['message_id'] = $message->meta['context']['id'];
        }

        return $payload;
    }

    public function replaceShortcode(): self
    {
        $modifiedBody = $this->message->body;

        if (isset($modifiedBody['body']) && is_string($modifiedBody['body'])) {
            $modifiedBody['body'] = self::replaceText($modifiedBody['body']);
        }

        $this->message->body = $modifiedBody;

        return $this;
    }

    public function replaceText(string $messageText): string
    {
        return str_replace(
            array_keys($this->shortCodes),
            array_values($this->shortCodes),
            $messageText
        );
    }

    public function downloadAttachment(string $mediaId, string $payloadKey): Message
    {
        $message = $this->message;

        validateUserPlan('storage');

        $mimeType = $message->getBody('mime_type');
        $extension = $this->getExtension($mimeType);

        if (! $extension) {
            throw new \InvalidArgumentException("Unsupported file type: {$mimeType}");
        }

        $platform = $message->platform;
        $accessToken = $platform->access_token;
        $phoneNumberId = $platform->uuid;

        $waClient = WhatsappClient::make($accessToken, $phoneNumberId);

        $mediaInfoRes = $waClient->getMediaInfo($mediaId)->throw(
            fn ($res) => throw new \Exception('Failed to get media info: '.$res->json('error.message'))
        );

        $fileUrl = $mediaInfoRes->json('url');
        $fileRes = $waClient->getMedia($fileUrl)->throw(
            fn ($res) => throw new \Exception('Failed to download media: '.$res->json('error.message'))
        );

        $mediaUrl = $this->uploadBodyContent($fileRes->getBody(), $extension);

        throw_unless($mediaUrl, \Exception::class, 'Failed to upload media');

        $messageBody = $message->body;
        data_set($messageBody, $payloadKey, $mediaUrl);
        $message->update(['body' => $messageBody]);

        return $this->message;
    }
}
