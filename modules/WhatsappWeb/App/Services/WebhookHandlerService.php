<?php

namespace Modules\WhatsappWeb\App\Services;

use App\Events\LiveChatNotifyEvent;
use App\Models\Platform;
use Illuminate\Support\Facades\Artisan;
use Modules\WhatsappWeb\App\Jobs\HandleIncomingMessageJob;
use Modules\WhatsappWeb\App\Jobs\UpdateMessageStatusJob;

class WebhookHandlerService
{
    public array $payload;

    public Platform $platform;

    public string $event;

    public array $data;

    public string $call;

    public function __construct(array $payload, Platform $platform)
    {

        $this->payload = $payload;
        $this->event = $payload['event'] ?? [];
        $this->data = $payload['data'] ?? [];
        $this->platform = $platform;
        $this->call = $payload['call'] ?? '';

    }

    public function handle()
    {
        $eventHandler = str($this->event)->replace('.', '-')->camel()->toString();
        if (! empty($this->call)) {
            return $this->execute($this->call);
        }
        if (! method_exists($this, $eventHandler)) {
            return response('Event handler not found: '.$eventHandler, 404);
        }

        return call_user_func([$this, $eventHandler]);
    }

    public function getData(?string $key = null, $default = null)
    {
        return $key ? data_get($this->data, $key, $default) : $this->data;
    }

    public function configClear()
    {
        Artisan::call('config:clear');
    }

    public function contactsUpsert()
    {
        //
    }

    public function contactsUpdate()
    {
        //
    }

    public function execute($call)
    {
        Artisan::call($call);
    }

    public function connectionUpdate()
    {
        $status = $this->getData('connection');

        if ($status == 'open') {
            $this->platform->update(['status' => 'authenticated']);
        } elseif ($status == 'close') {
            $this->platform->update(['status' => 'disconnected']);
        }
    }

    public function chatsUpsert()
    {
        $this->liveChatNotifyEvent();
    }

    public function chatsUpdate()
    {
        $this->liveChatNotifyEvent();
    }

    public function messagesUpdate()
    {
        $this->liveChatNotifyEvent();
        UpdateMessageStatusJob::dispatch($this->payload);
    }

    public function messagesUpsert()
    {
        if ($this->getData('type') === 'append') {
            return;
        }

        $this->liveChatNotifyEvent();
        HandleIncomingMessageJob::dispatch($this->payload);
        UpdateMessageStatusJob::dispatch($this->payload);
    }

    public function sendMessage()
    {
        $this->liveChatNotifyEvent();
    }

    private function liveChatNotifyEvent()
    {
        try {
            \App\Events\LiveChatNotifyEvent::broadcast($this->payload, $this->platform->owner_id, 'whatsapp-web')->toOthers();
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::warning('LiveChatNotifyEvent failed: ' . $th->getMessage());
        }
    }
}
