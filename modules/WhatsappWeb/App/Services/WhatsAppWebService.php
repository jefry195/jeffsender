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

        return Http::baseUrl(url: $baseApiUrl)->timeout(300)->withHeaders([
            'X-API-Key' => '12345',
        ]);
    }

    public function sessionList()
    {
        return $this->apiClient()->get('/sessions/list');
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
        if ($messageType === 'list') {
            if (isset($message['text'])) {
                $message['text'] = $this->replaceShortCodes($message['text'], $jid, $sessionId);
            }
            if (isset($message['title'])) {
                $message['title'] = $this->replaceShortCodes($message['title'], $jid, $sessionId);
            }
            if (isset($message['footer'])) {
                $message['footer'] = $this->replaceShortCodes($message['footer'], $jid, $sessionId);
            }
        }

        $data = [
            'receiver' => $jid,
            'isGroup' => $type !== 'number',
            'message' => [],
        ];

        if (! empty($options)) {
            $data['options'] = $options;
        }

        $data['message'] = match ($messageType) {
            'text' => ['text' => $this->replaceShortCodes($message['text'], $jid, $sessionId)],
            'location' => [
                'location' => [
                    'degreesLatitude' => $message['latitude'],
                    'degreesLongitude' => $message['longitude'],
                ],
            ],
            'video' => [
                'video' => ['url' => $this->resolveMediaUrl($message['video'])],
                'caption' => $message['caption'] ?? null,
                'gifPlayback' => $message['gifPlayback'] == 1 ? true : false,
                'ptv' => false,
            ],
            'audio' => [
                'audio' => ['url' => $this->resolveMediaUrl($message['audio'])],
                'mimetype' => 'audio/mp4',
                'ptt' => false,
            ],
            'voice' => [
                'audio' => [
                    'url' => $this->resolveMediaUrl($message['voice']),
                ],
                'mimetype' => 'audio/ogg',
                'ptt' => true,
            ],
            'image' => [
                'image' => ['url' => $this->resolveMediaUrl($message['image'])],
                'caption' => $message['caption'] ?? null,
            ],
            'document' => [
                'document' => ['url' => $this->resolveMediaUrl($message['document'])],
                'caption' => $message['caption'] ?? null,
            ],
            'poll' => [
                'poll' => [
                    'name' => $message['name'],
                    'values' => $message['values'],
                    'selectableCount' => $message['selectableCount'] ?? 1,
                ],
            ],
            'template' => match (true) {
                isset($message['text']) => ['text' => $this->replaceShortCodes($message['text'], $jid, $sessionId)],
                isset($message['image']) => ['image' => ['url' => $this->resolveMediaUrl($message['image'])], 'caption' => $message['caption'] ?? null],
                isset($message['video']) => ['video' => ['url' => $this->resolveMediaUrl($message['video'])], 'caption' => $message['caption'] ?? null],
                isset($message['document']) => ['document' => ['url' => $this->resolveMediaUrl($message['document'])], 'caption' => $message['caption'] ?? null],
                isset($message['audio']) => ['audio' => ['url' => $this->resolveMediaUrl($message['audio'])], 'mimetype' => 'audio/mp4'],
                isset($message['location']) => ['location' => ['degreesLatitude' => $message['latitude'], 'degreesLongitude' => $message['longitude']]],
                default => throw new \InvalidArgumentException("Unsupported template message format"),
            },
            'list' => [
                'viewOnceMessage' => [
                    'message' => [
                        'messageContextInfo' => [
                            'deviceListMetadata' => (object)[],
                            'deviceListMetadataVersion' => 2,
                        ],
                        'interactiveMessage' => [
                            'body' => [
                                'text' => $message['text'] ?? '',
                            ],
                            'footer' => [
                                'text' => $message['footer'] ?? '',
                            ],
                            'header' => [
                                'title' => $message['title'] ?? '',
                                'hasMediaAttachment' => false,
                            ],
                            'nativeFlowMessage' => [
                                'buttons' => [
                                    [
                                        'name' => 'single_select',
                                        'buttonParamsJson' => json_encode([
                                            'title' => $message['button_text'] ?? 'Select Option',
                                            'sections' => array_map(function ($section) {
                                                return [
                                                    'title' => $section['title'] ?? 'Options',
                                                    'rows' => array_map(function ($row) {
                                                        return [
                                                            'title' => $row['title'] ?? '',
                                                            'id' => !empty($row['rowId']) ? $row['rowId'] : (!empty($row['id']) ? $row['id'] : uniqid()),
                                                            'description' => $row['description'] ?? '',
                                                                ];
                                                    }, $section['rows'] ?? []),
                                                ];
                                            }, $message['sections'] ?? []),
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
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

    private function resolveMediaUrl($url)
    {
        if (empty($url)) {
            return $url;
        }

        // If it's already an absolute path (starts with / or C:\)
        if (str_starts_with($url, '/') || str_contains($url, ':\\')) {
            if (file_exists($url)) {
                return $url;
            }
        }

        // Parse the URL to get the path segment (e.g. /uploads/...)
        $parsedUrl = parse_url($url, PHP_URL_PATH);
        if ($parsedUrl) {
            // Check if it exists in public directory
            $path = public_path(ltrim($parsedUrl, '/'));
            if (file_exists($path)) {
                return $path;
            }

            // Handle cases where the path might have /storage prefix but mapped differently
            $relativePath = str_replace('/storage/', '/', $parsedUrl);
            $pathAlt = public_path(ltrim($relativePath, '/'));
            if (file_exists($pathAlt)) {
                return $pathAlt;
            }
        }

        return $url;
    }

    private function addPlatformLog($sessionId, $messageType, $response)
    {
        $platform = Platform::query()->where('uuid', $sessionId)->firstOrFail();
        
        $text = $response->json('data.message.message.extendedTextMessage.text') 
            ?? $response->json('data.message.message.conversation') 
            ?? '';

        return $platform->logs()->create([
            'module' => 'whatsapp-web',
            'owner_id' => $platform->owner_id,
            'direction' => 'out',
            'message_type' => $messageType,
            'message_text' => $text,
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

    public function getGroups(string $sessionId)
    {
        return $this->apiClient()->get('/groups', ['id' => $sessionId])->json();
    }

    public function getGroupMetaData(string $sessionId, string $groupId)
    {
        return $this->apiClient()->get("/groups/meta/{$groupId}", ['id' => $sessionId])->json();
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

    public function replaceShortCodes($text, $jid, $sessionId = null)
    {
        $customerName = Chat::where('id', $jid)->value('name');
        
        $platformUuid = $sessionId ?? '';
        $baseUrl = rtrim(config('app.url', 'http://127.0.0.1:8010'), '/');
        $orderLink = $platformUuid ? "{$baseUrl}/order/{$platformUuid}" : "{$baseUrl}/order";

        return str($text)
            ->replace('{name}', $customerName ?? '{name}')
            ->replace('{platform_uuid}', $platformUuid)
            ->replace('{order_link}', $orderLink)
            ->toString();
    }

    private function convertListToText(array $message): string
    {
        $text = "";
        if (!empty($message['title'])) {
            $text .= "*" . trim($message['title']) . "*\n\n";
        }
        if (!empty($message['text'])) {
            $text .= trim($message['text']) . "\n\n";
        }
        
        if (!empty($message['sections'])) {
            foreach ($message['sections'] as $section) {
                if (!empty($section['title'])) {
                    $text .= "*" . trim($section['title']) . "*\n";
                }
                if (!empty($section['rows'])) {
                    foreach ($section['rows'] as $row) {
                        $rowTitle = trim($row['title'] ?? '');
                        $rowDesc = trim($row['description'] ?? '');
                        $rowId = !empty($row['rowId']) ? $row['rowId'] : (!empty($row['id']) ? $row['id'] : '');
                        
                        $text .= "• *" . $rowTitle . "*";
                        if ($rowDesc) {
                            $text .= " - " . $rowDesc;
                        }
                        if ($rowId) {
                            $text .= " (Ketik: *" . $rowId . "*)";
                        }
                        $text .= "\n";
                    }
                }
                $text .= "\n";
            }
        }
        
        if (!empty($message['footer'])) {
            $text .= "_" . trim($message['footer']) . "_\n";
        }
        
        return trim($text);
    }
}
