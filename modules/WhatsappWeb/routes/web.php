<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsappWeb\App\Http\Controllers as MODULE;

Route::group(['middleware' => ['auth', 'user', 'access_module:whatsapp-web']], function () {
    Route::get('overview', MODULE\DashboardController::class)->name('overview');

    // Chats
    Route::resource('platforms', MODULE\PlatformController::class)->except(['edit', 'show']);
    Route::get('/platforms/{uuid}/connection', [MODULE\PlatformController::class, 'connection'])->name('platforms.connection');
    Route::get('platforms/{platform_uuid}/conversations', [MODULE\PlatformConversationController::class, 'index'])->name('platforms.conversations.index');

    // templates
    Route::resource('templates', MODULE\TemplateController::class);

    // send messages
    Route::get('send-message', [MODULE\SendMessageController::class, 'create'])
        ->name('send-message.create');
    Route::post('send-message', [MODULE\SendMessageController::class, 'store'])
        ->name('send-message.store');

    // send bulk messages
    Route::resource('send-bulk-message', MODULE\BulkSendController::class)
        ->only(['create', 'store', 'index'])
        ->names('send-bulk-message');

    // customers
    Route::post('customers/import-from-device', [MODULE\CustomerController::class, 'importFromDevice'])->name('customers.import-from-device');
    Route::get('customers/groups-by-platform', [MODULE\CustomerController::class, 'getGroupsByPlatform'])->name('customers.groups-by-platform');
    Route::post('customers/import-from-group', [MODULE\CustomerController::class, 'importFromGroup'])->name('customers.import-from-group');
    Route::post('customers/import-from-scraping', [MODULE\CustomerController::class, 'importFromScrapeData'])
        ->name('customers.import-from-scraping');
    Route::post('customers/bulk-import', [MODULE\CustomerController::class, 'bulkImport'])->name('customers.bulk-import');
    Route::post('customers/bulk-delete', [MODULE\CustomerController::class, 'bulkDelete'])->name('customers.bulk-delete');
    Route::post('customers/bulk-verify', [MODULE\CustomerController::class, 'bulkVerify'])->name('customers.bulk-verify');
    Route::post('customers/bulk-assign-group', [MODULE\CustomerController::class, 'bulkAssignGroup'])->name('customers.bulk-assign-group');
    Route::resource('customers', MODULE\CustomerController::class);

    // groups
    Route::resource('groups', MODULE\GroupController::class);
    Route::patch('groups/{group}/update-customers', [MODULE\GroupController::class, 'updateCustomers'])
        ->name('groups.update-customers');

    // Simulasi Campaign (test tanpa kirim pesan sungguhan)
    Route::get('simulation', [MODULE\SimulationController::class, 'index'])->name('simulation.index');
    Route::post('simulation/run', [MODULE\SimulationController::class, 'run'])->name('simulation.run');

    // Campaigns
    Route::resource('campaigns', MODULE\CampaignController::class)->except(['update']);
    Route::get('campaigns/{campaign}/resume', [MODULE\CampaignController::class, 'resume'])->name('campaigns.resume');
    Route::get('campaigns/{campaign}/pause', [MODULE\CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::patch('campaigns/{campaign}/update-status', [MODULE\CampaignController::class, 'updateStatus'])->name('campaigns.update-status');

    // quick replies
    Route::resource('quick-replies', '\App\Http\Controllers\User\QuickReplyController')->except('show');

    // auto replies
    Route::resource('auto-replies', MODULE\AutoReplyController::class);

    // apps
    Route::get('apps/{app}/logs', [MODULE\AppController::class, 'logs'])->name('apps.logs');
    Route::resource('apps', MODULE\AppController::class);

    // warmer
    Route::resource('warmer', MODULE\WarmerController::class)->names('warmer');
    Route::post('warmer-message', [MODULE\WarmerController::class, 'sendMessage'])
        ->name('warmer.send-message');

    // Calculator
    Route::get('calculator', [MODULE\CalculatorController::class, 'index'])->name('calculator.index');
    Route::post('calculator/calculate', [MODULE\CalculatorController::class, 'calculate'])->name('calculator.calculate');
});
