<?php

use Illuminate\Support\Facades\Route;
use Modules\Whatsapp\App\Http\Controllers\Api\WhatsAppSMSController;
use Modules\Whatsapp\App\Http\Controllers\WebhookController;
use Nwidart\Modules\Facades\Module;

Route::get('device/{uuid}/webhook', [WebhookController::class, 'verify'])->name('webhook');
Route::post('device/{uuid}/webhook', [WebhookController::class, 'store']);

Route::post('message', [WhatsAppSMSController::class, 'sendMessage']);
Route::get('embed-login/check-module-status', function () {

    if (Module::has('WhatsappES')) {
        $checkData = [
            'is_active' => true,
            'status' => __('WhatsappCloud Embed Login Module is active!'),
        ];

        return response()->json($checkData);
    }

    $checkData = [
        'is_active' => false,
        'status' => __('WhatsappCloud Embed Login Module is not found!'),
    ];

    return response()->json($checkData);
})->name('embed-login.check-module-status');
