<?php

namespace App\Helpers;

use App\Contracts\ChatServiceContract;
use App\Contracts\MessageComposerContract;
use App\Contracts\MessageServiceContract;
use App\Contracts\ReplyServiceContract;
use App\Models\Message;

final class ModuleServiceResolver
{
    public static function resolve(string $module, string $serviceClass, array $parameters = []): object
    {
        $module = str($module)->studly()->toString();
        $serviceClass = str($serviceClass)->studly()->toString();

        return app("Modules\\{$module}\\App\\Services\\{$serviceClass}", $parameters);
    }

    public static function resolveMessageService(Message $message): MessageServiceContract
    {
        $module = str($message->module)->studly()->toString();

        return app("Modules\\{$module}\\App\\Services\\MessageService", ['message' => $message]);
    }

    public static function resolveComposerService(string $module): MessageComposerContract
    {
        return self::resolve($module, 'MessageComposer');
    }

    public static function resolveChatService(string $module): ChatServiceContract
    {
        return self::resolve($module, 'ChatService');
    }

    public static function resolveReplyService(string $module, array $parameters = []): ReplyServiceContract
    {
        return self::resolve($module, 'ReplyService', $parameters);
    }
}
