<?php

namespace App\Contracts;

interface ReplyServiceContract
{
    public function using(int|string $datasetId, string $messageText, array $data = []): static;

    public function process(): static;

    public function getMessages(): array;
}
