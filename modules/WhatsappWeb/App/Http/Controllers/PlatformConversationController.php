<?php

namespace Modules\WhatsappWeb\App\Http\Controllers;

use App\Helpers\PageHeader;
use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Inertia\Inertia;
use Nwidart\Modules\Facades\Module;

class PlatformConversationController extends Controller
{
    public function index($platformUuid)
    {
        $platform = activeWorkspaceOwner()->platforms()->whatsappWeb()->where('uuid', $platformUuid)->firstOrFail();

        PageHeader::set()->title('Whatsapp Chats')
            ->buttons([
                [
                    'text' => 'Back',
                    'url' => route('user.whatsapp-web.platforms.index'),
                ],
            ]);
        $languages = json_decode(file_get_contents(base_path('database/json/languages.json')), true);
        $languages = array_values(array_map(function ($language) {
            return [
                'id' => $language['id'],
                'name' => $language['name'],
            ];
        }, $languages));

        $moduleFeatures = Module::find('WhatsappWeb')->get('features');
        $chatService = new ChatService('whatsapp-web', $platform);

        return Inertia::render('Chats/Index', [
            'platforms' => [$platform],
            'languages' => $languages,
            'api_base_url' => url('api/whatsapp-web/v1'),
            'chat_templates' => $chatService->templates(),
            'quick_replies' => $chatService->quickReplyTemplates(),
            'badges' => $chatService->badges(),
            'module_features' => $moduleFeatures,
        ]);
    }
}
