<?php

namespace App\Abstracts;

use App\Contracts\ReplyServiceContract;

abstract class ReplyServiceAbstract implements ReplyServiceContract
{
    public int|string $datasetId;

    public string $messageText;

    public array $messages = [];

    public array $data = [];

    public function using(int|string $datasetId, string $messageText, array $data = []): static
    {
        $this->datasetId = $datasetId;
        $this->messageText = $messageText;
        $this->data = $data;

        return $this;
    }

    public function process(): static
    {
        return $this;
    }

    public function getData(?string $key = null, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    public function addData(string $key, $value): static
    {
        data_set($this->data, $key, $value);

        return $this;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function addMessage(string $type, array $body): static
    {
        $this->messages[] = [
            'type' => $type,
            'body' => $body,
        ];

        return $this;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
