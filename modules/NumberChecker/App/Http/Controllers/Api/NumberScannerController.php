<?php

namespace Modules\NumberChecker\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use Illuminate\Http\Request;
use Modules\NumberChecker\App\Models\NumberScanner;
use Modules\WhatsappWeb\App\Services\WhatsAppWebService;

class NumberScannerController extends Controller
{
    public function scanner(Request $request, WhatsAppWebService $whatsAppWebService)
    {
        $request->validate([
            'contact' => 'required|array',
            'contact.phone' => 'required|string',
            'platform_id' => 'required|numeric|exists:platforms,id',
        ]);
        $user = activeWorkspaceOwner();
        $platform = Platform::where('id', $request->platform_id)
            ->where('owner_id', $user->id)
            ->where('module', 'whatsapp-web')
            ->firstOrFail();
        $number = $request->contact['phone'];

        $res = $whatsAppWebService->checkNumber($platform->uuid, $number);

        

        $isNumberExists = $res->json('data.0.exists', false);

        if (! $isNumberExists) {
            return response()->json(['exists' => false, 'message' => $res->json('message')]);
        }

        $numberScanner = NumberScanner::firstOrCreate(
            ['user_id' => $user->id],
            ['number_scanned' => 0]
        );

        $numberScanner->increment('number_scanned');

        return response()->json(['exists' => $isNumberExists, 'data' => $res->json('data.0')]);
    }
}
