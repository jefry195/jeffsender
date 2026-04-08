<?php

namespace App\Contracts;

abstract class MessageParserAbstract
{
    private array $payload;

    private string $direction;

    private string $type;

    public function __construct(array $payload, string $direction)
    {
        $this->payload = $payload;
    }

    abstract protected function detectType(): string;

    abstract protected function incoming(): array;

    abstract protected function outgoing(): array;

    public function getPayload(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->payload;
        }

        return data_get($this->payload, $key, $default);
    }

    public function generateBody(): array
    {
        return match ($this->detectType()) {
            'incoming' => $this->incoming(),
            'outgoing' => $this->outgoing(),
            default => [],
        };
    }
}
