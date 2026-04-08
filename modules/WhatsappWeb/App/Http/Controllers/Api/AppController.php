<?php

namespace Modules\WhatsappWeb\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\WhatsappWeb\App\Models\WhatsappWebApp;
use Modules\WhatsappWeb\App\Services\WhatsAppWebService;

class AppController extends Controller
{
    public function sendMessage(Request $request, WhatsAppWebService $whatsAppWebService)
    {
       
        $request->validate([
            'app_key' => ['required', 'exists:whatsapp_web_apps,key'],
            'auth_key' => ['required', 'exists:users,authKey'],
            'to' => ['required', 'string'],
            'type' => ['sometimes', 'in:text,image,audio,video,document'],
            'message' => ['required_if:type,text', 'string', 'max:1000'],
            'url' => [
                Rule::requiredIf(function () use ($request) {
                    return in_array($request->get('type'), ['image', 'audio', 'video', 'document']);
                }),
                'url',
            ],
        ]);

        $appUser = User::query()
            ->where(
                'authKey',
                $request->get('auth_key')
            )
            ->first();

        $app = WhatsappWebApp::query()
            ->where('user_id', $appUser?->id)
            ->where(
                'key',
                $request->get('app_key')
            )
            ->first();

        if (! $app || ! $appUser) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication failed',
            ], 401);
        }

        $platformUuid = $app->platform?->uuid;

        if (! $platformUuid) {
            return response()->json([
                'success' => false,
                'error' => 'Platform not found',
            ], 404);
        }

        $jid = $request->get('to').'@s.whatsapp.net';

        $type = $request->get('type', 'text');
        $message = [];

        if ($type === 'text') {
            $message = ['text' => $request->get('message')];
        } else {
            $message = [$type => $request->get('url')];
        }

        DB::beginTransaction();
        try {

            $res = $whatsAppWebService->sendMessage(
                $platformUuid,
                $jid,
                $message,
                $type
            );

            $app->logs()->create([
                'owner_id' => $appUser->id,
                'platform_id' => $app->platform_id,
                'to' => $request->get('to'),
                'status_code' => $res->status(),
                'request' => [
                    'sessionId' => $platformUuid,
                    'jid' => '',
                    'message' => $message,
                ],
                'response' => $res->json(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $res->json(),
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Unable to connect to server',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
