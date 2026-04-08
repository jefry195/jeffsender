<?php

namespace Modules\Whatsapp\App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

class WhatsappClient
{
    private $apiBaseUrl = 'https://graph.facebook.com';
    private $apiVersion = 'v23.0';

    private $apiClient;

    public function __construct(private ?string $accessToken = null, private ?string $phoneNumberId)
    {
        $this->apiClient = Http::acceptJson()
            ->baseUrl("{$this->apiBaseUrl}/{$this->apiVersion}")
            ->withToken($this->accessToken);
    }

    public function setToken(string $accessToken): PendingRequest
    {
        return $this->apiClient->withToken($accessToken);
    }

    public function cloudMediaUpload(array $attributes): Response
    {
        return $this->apiClient->post(
            "$this->phoneNumberId/media",
            $attributes
        );
    }

    public function getMediaInfo(string $mediaId): Response
    {
        return $this->apiClient->get("/$mediaId");
    }

    public function getMedia(string $mediaUrl): Response
    {
        return Http::withToken($this->accessToken)->get($mediaUrl);
    }

    public function getTemplates(string $businessAccountId): Response
    {
        return $this->apiClient->get("$businessAccountId/message_templates");
    }

    public function postMessage(array $payload): Response
    {
        return $this->apiClient->post("$this->phoneNumberId/messages", $payload);
    }

    public function markAsRead(array $payload): Response
    {
        return $this->apiClient->post("$this->phoneNumberId/messages", $payload);
    }

    /**
     * Creates a new instance of the class.
     *
     * @param string $token
     * @param string|null $phoneNumberId
     * @return self
     */
    public static function make(string $token, ?string $phoneNumberId = null): self
    {
        return new self($token, $phoneNumberId);
    }
}
