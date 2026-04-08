<?php

namespace Modules\WhatsappWeb\App\Services;

use App\Models\Chat;
use App\Models\Platform;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class WhatsAppWebService
{
    public function apiClient()
    {
        $baseApiUrl = Config::get('whatsapp-web.base_url') ?? '';

        return Http::baseUrl(url: $baseApiUrl)->withHeaders([
            'X-API-Key' => '12345',
        ]);
    }

    public function sessionList()
    {
        return $this->apiClient()->get('/sessions');
    }

    public function setJid(string $jid)
    {
        $jid = str_replace('+','',$jid);
        return "{$jid}@s.whatsapp.net";
    }

    public function addSession(string $sessionId, ...$args): array
    {
        $parameters = [
            'id' => $sessionId,
            'typeAuth' => 'qr',
        ];
        if ($this->findSession($sessionId)->successful()) {
            $this->deleteSession($sessionId);
            sleep(5);
        }
        if (count($args) > 0) {
            $parameters = array_merge($parameters, ...$args);
        }
        $response = $this->apiClient()->post('/sessions/add', $parameters);

        return $response->json();
    }

    public function deleteSession(string $sessionId)
    {
        $response = $this->apiClient()->delete("/sessions/delete/{$sessionId}");

        return $response;
    }

    public function findSession(string $sessionId)
    {
        $response = $this->apiClient()->get("/sessions/find/{$sessionId}");

        return $response;
    }

    public function getSessionQr(string $sessionId): array
    {
        return $this->apiClient()->get("/sessions/{$sessionId}/qr")->json();
    }

    public function configClear()
    {
        config(['app.env' => 'local']);

        return \Artisan::call('config:clear');
    }

    public function execute($call)
    {
        config(['app.env' => 'local']);

        return \Artisan::call($call);
    }

    public function getSessionsStatus(string $sessionId)
    {
        return $this->apiClient()->get("/sessions/status/{$sessionId}");
    }

    public function checkNumber(string $sessionId, $number)
    {
        return $this->apiClient()->post("misc/check-on-whatsapp?id={$sessionId}", [
            'jid' => $number,
        ]);
    }

    public function sendMessage(string $sessionId, string $jid, array $message, string $messageType = 'text', string $type = 'number', array $options = [])
    {
        $data = [
            'receiver' => $jid,
            'isGroup' => $type !== 'number',
            'message' => [],
        ];

        if (! empty($options)) {
            $data['options'] = $options;
        }

        $data['message'] = match ($messageType) {
            'text' => ['text' => $this->replaceShortCodes($message['text'], $jid)],
            'location' => [
                'location' => [
                    'degreesLatitude' => $message['latitude'],
                    'degreesLongitude' => $message['longitude'],
                ],
            ],
            'video' => [
                'video' => ['url' => $message['video']],
                'caption' => $message['caption'] ?? null,
                'gifPlayback' => $message['gifPlayback'] == 1 ? true : false,
                'ptv' => false,
            ],
            'audio' => [
                'audio' => ['url' => $message['audio']],
                'mimetype' => 'audio/mp4',
                'ptt' => false,
            ],
            'voice' => [
                'audio' => [
                    'url' => $message['voice'],
                ],
                'mimetype' => 'audio/ogg',
                'ptt' => true,
            ],
            'image' => [
                'image' => ['url' => $message['image']],
                'caption' => $message['caption'] ?? null,
            ],
            'document' => [
                'document' => ['url' => $message['document']],
                'caption' => $message['caption'] ?? null,
            ],
            'poll' => [
                'poll' => [
                    'name' => $message['name'],
                    'values' => $message['values'],
                    'selectableCount' => $message['selectableCount'] ?? 1,
                ],
            ],
            default => throw new \InvalidArgumentException("Unsupported message type: {$messageType}"),
        };

        $response = $this->apiClient()->post("/chats/send?id={$sessionId}", $data);

        if ($response->successful()) {
            $this->addPlatformLog($sessionId, $messageType, $response);
        }

        return $response;
    }

    private function addPlatformLog($sessionId, $messageType, $response)
    {
        $platform = Platform::query()->where('uuid', $sessionId)->firstOrFail();

        return $platform->logs()->create([
            'module' => 'whatsapp-web',
            'owner_id' => $platform->owner_id,
            'direction' => 'out',
            'message_type' => $messageType,
            'message_text' => $response->json('message.extendedTextMessage.text'),
            'meta' => $response->json(),
        ]);
    }

    public function getChats(array $queryParams = [])
    {
        return $this->apiClient()->get('/chats', $queryParams)->json();
    }

    public function getChatMessages(string $chatId, array $queryParams = [])
    {
        return $this->apiClient()->get("chats/$chatId", $queryParams)->json();
    }

    public function readChat(string $sessionId, string $jid): Response
    {
        return $this->apiClient()->post("chats/mark-as-read?id={$sessionId}", [
            'jid' => $jid,
        ]);
    }

    public function readMessages(string $sessionId, array $keys = [])
    {
        return $this->apiClient()->post('/chats/read', [
            'id' => $sessionId,
            'keys' => $keys,
        ])->json();
    }

    public function getGroupMeta(string $sessionId, string $groupId, array $queryParams = [])
    {
        return $this->apiClient()->get("$sessionId/groups/$groupId", $queryParams)->json();
    }

    public function getMediaBuffer(string $sessionId, string $remoteJid, string $messageId): Response
    {
        return $this->apiClient()
            ->withHeaders([
                'Accept' => 'application/octet-stream',
            ])
            ->post("chats/download-media-buffer?id={$sessionId}", [
                'remoteJid' => $remoteJid,
                'messageId' => $messageId,
            ]);
    }

    public function getContactPhoto(string $sessionId, string $jid)
    {
        return $this->apiClient()->post("misc/profile-picture?id={$sessionId}", [
            'jid' => $jid,
            'isGroup' => false,
        ]);
    }

    public function replaceShortCodes($text, $jid)
    {
        $customerName = Chat::where('id', $jid)->value('name');

        return str($text)->replace('{name}', $customerName ?? '{name}')->toString();
    }
}
