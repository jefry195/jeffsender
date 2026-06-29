<?php

use App\Http\Controllers\Web as WEB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\GoogleAuthController;



Route::patch('set-locale/{locale}', [LocaleController::class, 'update'])->name('set-locale');


// common
Route::get('/', [WEB\WebPageController::class, 'home'])->name('home');
Route::get('/about-us', [WEB\WebPageController::class, 'about']);
Route::get('/pricing', [WEB\WebPageController::class, 'pricing']);
Route::resource('/contact-us', WEB\ContactController::class)->only('index', 'store');
Route::get('/faq', [WEB\WebPageController::class, 'faq']);
Route::get('faq-category/{slug}', [WEB\WebPageController::class, 'faqCategory'])->name('faq-category');

// pages
Route::get('/team', [WEB\WebPageController::class, 'team']);
Route::get('/integrations', [WEB\WebPageController::class, 'integrations']);
Route::resource('/services', WEB\ServiceController::class)->only(['index', 'show']);
Route::get('/service-category/{slug}', [WEB\ServiceController::class, 'categoryShow'])->name('service-category');

// blogs
Route::resource('/blogs', WEB\BlogController::class)->only(['index', 'show']);
Route::get('blogs/category/{slug}', [WEB\BlogController::class, 'category'])->name('blogs.category');
Route::get('blogs/tag/{slug}', [WEB\BlogController::class, 'tag'])->name('blogs.tag');

// newsletter
Route::post('/subscribe', [WEB\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/subscribed', [WEB\NewsletterController::class, 'subscribed'])->name('newsletter.subscribed');

// others
Route::post('/credit/pay', [WEB\CreditPayController::class, 'store'])->name('credit.pay');
Route::get('/credit/pay/{status}', [WEB\CreditPayController::class, 'status']);
Route::get('/ai-tools', [WEB\WebPageController::class, 'aiTools'])->name('ai-tools.index');


Route::get('/oauth/google', [GoogleAuthController::class, 'redirectTo']);
Route::get('/oauth/google/callback', [GoogleAuthController::class, 'handleCallback']);

if (!function_exists('getNextOrderNo')) {
    function getNextOrderNo($app) {
        $nextOrderNo = null;
        if ($app && !empty($app->site_link)) {
            try {
                $sheetUrl = trim($app->site_link);
                $csvUrl = $sheetUrl;
                
                preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $sheetUrl, $idMatches);
                preg_match('/gid=([0-9]+)/', $sheetUrl, $gidMatches);
                
                if (!empty($idMatches[1])) {
                    $spreadsheetId = $idMatches[1];
                    $gid = isset($gidMatches[1]) ? $gidMatches[1] : '0';
                    $csvUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export?format=csv&id={$spreadsheetId}&gid={$gid}&t=" . time();
                }
                
                $response = \Illuminate\Support\Facades\Http::timeout(12)->get($csvUrl);
                if ($response->successful()) {
                    $csvData = $response->body();
                    $stream = fopen('php://temp', 'r+');
                    fwrite($stream, $csvData);
                    rewind($stream);

                    $lastOrderNo = null;
                    fgetcsv($stream); // skip headers
                    
                    while (($row = fgetcsv($stream)) !== false) {
                        if (isset($row[0])) {
                            $trimmed = trim($row[0]);
                            if (strpos($trimmed, 'ORD-') === 0) {
                                $lastOrderNo = $trimmed;
                            }
                        }
                    }
                    fclose($stream);

                    if ($lastOrderNo && preg_match('/ORD-(\d+)-(\d+)/', $lastOrderNo, $ordMatches)) {
                        $lastSerial = (int)$ordMatches[2];
                        $currentYear = date('Y');
                        $nextOrderNo = "ORD-{$currentYear}-" . ($lastSerial + 1);
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning("Failed to fetch next order number from Google Sheets: " . $e->getMessage());
            }
        }
        
        if (!$nextOrderNo) {
            $currentYear = date('Y');
            $randNo = rand(1000, 9999);
            $nextOrderNo = "ORD-{$currentYear}-{$randNo}";
        }
        
        return $nextOrderNo;
    }
}

Route::get('/order/{uuid?}', function ($uuid = null) {
    if ($uuid) {
        $platform = \App\Models\Platform::where('uuid', $uuid)->first();
        if (!$platform) {
            $platform = \App\Models\Platform::firstOrFail();
        }
    } else {
        $platform = \App\Models\Platform::firstOrFail();
    }
    
    $app = \Modules\WhatsappWeb\App\Models\WhatsappWebApp::where('platform_id', $platform->id)->first();
    $adminPhone = data_get($platform->meta, 'phone_number', '6282261567685');
    
    $nextOrderNo = getNextOrderNo($app);
    
    return view('order-form', [
        'appKey' => $app?->key ?? '',
        'authKey' => $app?->user?->authkey ?? '',
        'adminPhone' => $adminPhone,
        'nextOrderNo' => $nextOrderNo,
        'uuid' => $uuid
    ]);
})->name('public.order-form');

Route::get('/order-number/next/{uuid?}', function ($uuid = null) {
    if ($uuid) {
        $platform = \App\Models\Platform::where('uuid', $uuid)->first();
        if (!$platform) {
            $platform = \App\Models\Platform::first();
        }
    } else {
        $platform = \App\Models\Platform::first();
    }
    
    if (!$platform) {
        return response()->json(['error' => 'Platform not found'], 404);
    }
    
    $app = \Modules\WhatsappWeb\App\Models\WhatsappWebApp::where('platform_id', $platform->id)->first();
    $nextOrderNo = getNextOrderNo($app);
    
    return response()->json(['nextOrderNo' => $nextOrderNo]);
});


// custom page
Route::get('/{slug}', [WEB\WebPageController::class, 'page']);

