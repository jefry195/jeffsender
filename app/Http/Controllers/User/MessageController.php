<?php

namespace App\Http\Controllers\User;

use App\Helpers\ModuleServiceResolver;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Store a newly created message in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Message
     */
    public function store(SendMessageRequest $request)
    {
        $conversation = activeWorkspaceOwner()->conversations()->findOrFail($request->conversation_id);

        $messageType = $request->input('template.type', $request->input('type'));

        if ($conversation->module == 'telegram' && $messageType == 'image') {
            $messageType = 'photo';
        }

        $messageBody = ModuleServiceResolver::resolveComposerService($conversation->module)->composeBodyFromChatData($request->validated());

        $message = new Message([
            'module' => $conversation->module,
            'owner_id' => $conversation->owner_id,
            'platform_id' => $conversation->platform_id,
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'uuid' => null,
            'direction' => 'out',
            'type' => $messageType,
            'body' => $messageBody,
            'status' => 'pending',
            'meta' => $request->only('reply_message_uuid'),
        ]);

        return ModuleServiceResolver::resolveMessageService($message)
            ->replaceShortcode()
            ->send();
    }

    public function downloadAttachments(Request $request)
    {
        $request->validate([
            'message_uuid' => 'required|exists:messages,uuid',
            'attachment_id' => 'required|string',
            'attachment_key' => 'required|string',
        ]);

        $message = activeWorkspaceOwner()->messages()
            ->where('uuid', $request->message_uuid)
            ->firstOrFail();

        $messageService = ModuleServiceResolver::resolveMessageService($message);

        return $messageService->downloadAttachment(
            $request->input('attachment_id'),
            $request->get('attachment_key'),
        );
    }
}
