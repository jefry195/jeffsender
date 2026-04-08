<?php

namespace Modules\Whatsapp\App\Services;

use App\Contracts\MessageComposerContract;
use App\Models\Campaign;
use App\Models\Template;

class MessageComposer implements MessageComposerContract
{
    public static function composeBodyFromChatData(array $data): array
    {
        $type = $data['type'];
        $body = [];
        switch ($type) {
            case 'text':
                $body = [
                    'preview_url' => data_get($data, 'template.preview_url', false),
                    'body' => $data['text'] ?? data_get($data, 'template.body'),
                ];
                break;

            case 'audio':
                $body = ['link' => $data['attachments'][0]];
                break;
            case 'document':
            case 'image':
            case 'sticker':
            case 'video':

                $body = [
                    'link' => $data['attachments'][0],
                    'caption' => $data['caption'] ?? null,
                ];

                break;

            case 'template':
                $body = $data['template']['meta'];

                // Generate interactive components
                if (data_get($data, 'template.type') == 'template') {
                    $body = [
                        'name' => $data['template']['name'],
                        'language' => $data['template']['meta']['language'],
                        'components' => TemplateService::generateComponents($data['template']['meta']['components']),
                    ];
                }
                break;

            default:
                throw new \Exception("Message type not supported: $type");
        }

        return $body;
    }

    public static function composeBodyFromCampaign(Campaign $campaign): array
    {
        $body = [];

        if ($campaign->message_type === 'interactive') {
            $body = $campaign->meta;
        } elseif ($campaign->message_type === 'text') {
            $body = $campaign->meta;
        } else {
            $template = $campaign->meta;
            $body = [
                'name' => $template['name'],
                'language' => $template['language'],
                'components' => TemplateService::generateComponents($template['components']),
            ];
        }

        return $body;
    }

    public static function composeBodyFromFlowData(array $data): array
    {
        return match ($data['type']) {
            'text' => self::textMessage($data['body'], $data['preview_url']),
            'image', 'audio', 'video', 'document' => self::mediaMessage($data['link'], $data['caption']),
            'location' => self::locationMessage($data['latitude'], $data['longitude'], $data['name'], $data['address']),
            'button' => self::templateMessage('button', $data['body'], $data['action']),
            'list' => self::templateMessage('list', $data['body'], $data['action']),
            'contact' => self::contactMessage($data['body']),
            'cta' => self::templateMessage('cta', $data['body'], $data['action']),
            default => []
        };
    }

    public static function composeBodyFromTemplate(Template $template): array
    {
        return $template->meta;
    }

    public static function textMessage(string $body, ?bool $preview_url = false): array
    {
        return [
            'text' => $body,
            'preview_url' => $preview_url,
        ];
    }

    public static function mediaMessage(string $link, ?string $caption): array
    {
        return [
            'link' => $link,
            'caption' => $caption,
        ];
    }

    public static function locationMessage(
        float $latitude,
        float $longitude,
        string $name,
        string $address
    ): array {
        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'name' => $name,
            'address' => $address,
        ];
    }

    public static function contactMessage(array $data): array
    {
        return [
            'org' => $data['org'],
            'name' => $data['name'],
            'urls' => $data['urls'],
            'phones' => $data['phones'],
            'emails' => $data['emails'],
            'birthday' => $data['birthday'],
            'addresses' => $data['addresses'],
        ];
    }

    public static function templateMessage(string $type, array $body, array $action = []): array
    {
        return [
            'type' => $type,
            'body' => $body,
            'action' => $action,
        ];
    }
}
