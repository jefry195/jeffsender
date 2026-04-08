<?php

namespace App\Contracts;

use App\Models\Message;

interface ChatServiceContract
{
    public function generateMessage(array $messageData): Message;
}
