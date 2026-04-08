<?php

namespace App\Contracts;

use App\Models\Campaign;
use App\Models\Template;

interface MessageComposerContract
{
    public static function composeBodyFromChatData(array $bodyData): array;

    public static function composeBodyFromCampaign(Campaign $campaign): array;

    public static function composeBodyFromFlowData(array $bodyData): array;

    public static function composeBodyFromTemplate(Template $template): array;

    public static function textMessage(string $text): array;
}
