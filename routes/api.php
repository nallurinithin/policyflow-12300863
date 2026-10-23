<?php

use App\Http\Controllers\Api\CallbackController;
use App\Http\Controllers\Api\IssuanceController;
use App\Http\Controllers\Api\MockInsurerController;
use App\Http\Controllers\Api\PolicyRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — PolicyFlow Engine
|--------------------------------------------------------------------------
*/

// Policy Request Intake & Retrieval
Route::prefix('policy-requests')->group(function () {
    Route::post('/', [PolicyRequestController::class, 'store']);
    Route::get('/', [PolicyRequestController::class, 'index']);
    Route::get('/{id}', [PolicyRequestController::class, 'show']);

    // Issuance & Reconciliation Actions
    Route::post('/{id}/submit', [IssuanceController::class, 'submit']);
    Route::post('/{id}/reconcile', [IssuanceController::class, 'reconcile']);
});

// Mock Insurer & Callback Routes
Route::prefix('mock-insurer')->group(function () {
    Route::post('/submit', [MockInsurerController::class, 'submit']);
    Route::get('/status/{reference}', [MockInsurerController::class, 'getStatus']);
    Route::post('/callback', [CallbackController::class, 'handle']);
    Route::post('/trigger-callback', [MockInsurerController::class, 'triggerCallback']);
});
