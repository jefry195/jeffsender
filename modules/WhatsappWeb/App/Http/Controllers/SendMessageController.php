<?php

namespace Modules\WhatsappWeb\App\Http\Controllers;

use Inertia\Inertia;
use App\Helpers\PageHeader;
use App\Helpers\Toastr;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Platform;
use App\Traits\Uploader;
use Modules\WhatsappWeb\App\Services\WhatsAppWebService;

class SendMessageController extends Controller
{
    use Uploader;


    public function create()
    {
        PageHeader::set()->title('Send Message');
        /** @var \App\Models\User */
        $user = activeWorkspaceOwner();
        $platforms = $user->platforms()->whatsappWeb()->get();
        return Inertia::render('SendMessage/Create', [
            'platforms' => $platforms,
        ]);
    }

    public function store(Request $request, WhatsAppWebService $whatsAppWebService)
    {
        
         if(env('DEMO_MODE') && auth()->user()->id == 3){
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        validateWorkspacePlan('web_messages');

       

        $validated = $request->validate([
            'type' => 'required|in:text,image,video,audio,location,poll,document',
            'receiver_number' => 'required|string',
            'platform_id' => 'required|string',
            'meta' => 'required|array',
        ]);

         $platform = Platform::where('uuid', $request->get('platform_id'))
            ->where('owner_id', activeWorkspaceOwnerId())
            ->where('module', 'whatsapp-web')
            ->firstOrFail();

        $jid = $whatsAppWebService->setJid(str_replace('+','',$validated['receiver_number']));

        $templateController = new TemplateController();
        $metaValidation = $templateController->getMetaValidationRules($validated['type']);
        $request->validate($metaValidation);
        $meta = $templateController->saveFiles($request);

        $newMessage = $whatsAppWebService
            ->sendMessage($platform->uuid, $jid, $meta, $validated['type']);

        if ($request->filled('save_as_template') && $request->filled('name') && $request->save_as_template) {
            $templateController->store($request);
        }

        if ($newMessage->successful()) {
            Toastr::success(ucfirst($validated['type']) . ' Message sent successfully.');
        }

        return redirect()->back();
    }
}
