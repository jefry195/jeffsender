<?php

namespace App\Contracts;

use App\Models\Message;

interface MessageServiceContract
{
    public function __construct(Message $message);

    public function replaceShortcode(): self;

    public function send(): Message;

    public function downloadAttachment(string $mediaId, string $payloadKey): Message;
}
