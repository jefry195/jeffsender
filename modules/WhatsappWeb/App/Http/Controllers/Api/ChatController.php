<?php

namespace Modules\WhatsappWeb\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\WhatsappWeb\App\Services\WhatsAppWebService;

class ChatController extends Controller
{
    use Uploader;

    private WhatsAppWebService $chatService;

    public function __construct()
    {
        $this->chatService = new WhatsAppWebService;
    }

    public function chats(Request $request, string $sessionId)
    {
        $params = $request->merge([
            'id' => $sessionId,
        ])->toArray();

        return $this->chatService->getChats($params);
    }

    public function chatMessages(Request $request, string $sessionId, string $chatId)
    {
        $params = $request->merge([
            'id' => $sessionId,
            'limit' => $request->get('limit'),
            'cursorId' => $request->get('cursorId'),
        ])->toArray();

        return $this->chatService->getChatMessages($chatId, $params);
    }

    public function readChat(Request $request, string $sessionId, string $jid)
    {
        $res = $this->chatService->readChat($sessionId, $jid);

        return response()->json($res->json(), $res->status());
    }

    public function groupMessages(string $sessionId, string $chatId)
    {
        return $this->chatService->getGroupMeta($sessionId, $chatId, request()->all());
    }

    public function getMedia(Request $request, string $sessionId)
    {
        $response = $this->chatService->getMediaBuffer($sessionId, $request->input('remoteJid'), $request->input('id'));

        if ($response->failed()) {
            return response()->json($request->json(), $response->status());
        }

        return response($response->body(), 200)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="media-file"');
    }

    public function sendMessage(Request $request)
    {

        $message = $request->message;

        if ($request->messageType == 'voice') {
            $request->validate([
                'message.voice' => 'required',
            ], [
                'message.voice.required' => 'Voice message is required',
            ]);

            $file = $request->file('message.voice');
            $directory = 'uploads'.date('/y').'/'.date('m');
            $uploadedUri = $file->store($directory);
            $message['voice'] = Storage::url($uploadedUri);
        }

        $messageType = $request->messageType;
        $sendType = $request->type;
        $options = $request->options ?? [];

        $res = $this->chatService->sendMessage(
            $request->sessionId,
            $request->jid,
            $message,
            $messageType,
            $sendType,
            $options
        );

        return response()->json($res->json(), $res->status());

    }

    public function syncChat(Request $request, string $sessionId, string $jid)
    {
        $res = $this->chatService->getContactPhoto($sessionId, $jid);

        if ($res->failed()) {
            return response()->json($res->json(), $res->status());
        }

        $fileUrl = $res->json('data');

        $uploadedFileUrl = $this->saveFileFromUrl($fileUrl);

        $chat = Chat::query()->where([
            'sessionId' => $sessionId,
            'id' => $jid,
        ])->first();

        if (! $chat) {
            return response()->json([
                'error' => 'Chat not found',
            ], 404);
        }

        $name = $chat->name;

        if (empty($name)) {
            $contact = DB::table('Contact')
                ->where([
                    'sessionId' => $sessionId,
                    'id' => $jid,
                ])->first();

            if ($contact) {
                $name = $contact->name ?? $contact->verifiedName ?? $contact->notify ?? explode('@', $request->jid)[0] ?? '';
            }
        }

        $chat->update([
            'name' => $name,
            'picture' => $uploadedFileUrl]
        );

        return response()->json([
            'success' => 'File saved successfully',
            'url' => $uploadedFileUrl,
        ]);
    }
}
