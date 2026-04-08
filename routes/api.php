<?php

use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ShareholderController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    Route::apiResource('properties', PropertyController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('contracts', ContractController::class);
    Route::apiResource('sales', SaleController::class);
    Route::apiResource('debts', DebtController::class)->only(['index', 'show', 'update']);
    Route::apiResource('shareholders', ShareholderController::class);

    Route::prefix('accounting')->group(function (): void {
        Route::post('/revenues', [AccountingController::class, 'storeRevenue']);
        Route::post('/expenses', [AccountingController::class, 'storeExpense']);
        Route::get('/treasury', [AccountingController::class, 'treasury']);
        Route::post('/payments/contracts/{contract}', [AccountingController::class, 'receiveContractPayment']);
    });

    Route::get('/settings', [SettingsController::class, 'show']);
    Route::put('/settings', [SettingsController::class, 'update']);
});
