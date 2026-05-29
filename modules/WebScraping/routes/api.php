<?php

use Illuminate\Support\Facades\Route;
use Modules\WebScraping\App\Http\Controllers as MODULE;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 */

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Start scraping sebagai background job (langsung return)
    Route::post('scrape/{record}/start', [MODULE\Api\WebScrapingController::class, 'start'])
        ->name('scrape.start');

    // Cek status scraping (untuk polling dari frontend)
    Route::get('scrape/{record}/status', [MODULE\Api\WebScrapingController::class, 'status'])
        ->name('scrape.status');
});
