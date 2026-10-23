<?php

use App\Http\Controllers\Web\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — PolicyFlow Operational Portal
|--------------------------------------------------------------------------
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/policies/create', [DashboardController::class, 'create'])->name('policies.create');
Route::post('/policies', [DashboardController::class, 'store'])->name('policies.store');
Route::get('/policies/{id}', [DashboardController::class, 'show'])->name('policies.show');
Route::post('/policies/{id}/submit', [DashboardController::class, 'submit'])->name('policies.submit');
Route::post('/policies/{id}/reconcile', [DashboardController::class, 'reconcile'])->name('policies.reconcile');

// Reliability Sandbox
Route::get('/sandbox', [DashboardController::class, 'sandbox'])->name('sandbox');
