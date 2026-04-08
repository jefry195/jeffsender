<?php

use Illuminate\Support\Facades\Route;
use Modules\AiTraining\App\Http\Controllers\AiTrainingController;

// ai training
Route::resource('ai-training', AiTrainingController::class);
Route::delete('ai-training/{aiTraining}/record', [AiTrainingController::class, 'destroyRecord'])
    ->name('ai-training.destroy-record');
Route::get('ai-training/{aiTraining}/test-prompt', [AiTrainingController::class, 'testPrompt'])
    ->name('ai-training.test-prompt');
Route::patch('ai-training/{aiTraining}/status', [AiTrainingController::class, 'checkStatus'])
    ->name('ai-training.check-status');
Route::post('ai-training-credential', [AiTrainingController::class, 'storeCredentials'])
    ->name('ai-training-credential.store');
Route::delete('ai-training-credential/{provider}', [AiTrainingController::class, 'destroyCredentials'])
    ->name('ai-training-credential.destroy');
Route::post('ai-training-import-dataset', [AiTrainingController::class, 'importDataset'])
    ->name('ai-training.import-dataset');
Route::get('ai-training-sync/{provider}', [AiTrainingController::class, 'syncFineTuning'])
    ->name('ai-training.sync');
