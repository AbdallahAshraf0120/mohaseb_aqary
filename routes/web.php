<?php

use App\Http\Controllers\CashboxController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RemainingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\ShareholderController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

$modules = [
    'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
    'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
    'properties' => ['label' => 'عقارات', 'icon' => 'fa-building', 'route' => 'properties.index'],
    'clients' => ['label' => 'عملاء', 'icon' => 'fa-users', 'route' => 'clients.index'],
    'contracts' => ['label' => 'العقود', 'icon' => 'fa-file-signature', 'route' => 'contracts.index'],
    'sales' => ['label' => 'المبيعات', 'icon' => 'fa-cart-shopping', 'route' => 'sales.index'],
    'revenues' => ['label' => 'ايرادات', 'icon' => 'fa-money-bill-trend-up', 'route' => 'revenues.index'],
    'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'cashbox.index'],
    'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'expenses.index'],
    'debts' => ['label' => 'المديونيه', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
    'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
    'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
    'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
    'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'remaining.index'],
];

Route::get('/', fn () => redirect()->route('properties.index'))->name('home');

Route::post('/logout', function (): RedirectResponse {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('properties.index');
})->name('logout');

Route::get('/dashboard', function () use ($modules) {
    return view('dashboard', [
        'title' => 'Dashboard | Mohaseb Aqary',
        'pageTitle' => 'Dashboard',
        'modules' => $modules,
    ]);
})->name('dashboard');

Route::resource('properties', PropertyController::class);
Route::resource('areas', AreaController::class)->except(['show']);
Route::resource('shareholders', ShareholderController::class);
Route::resource('sales', SaleController::class);
Route::resource('clients', ClientController::class)->only(['index', 'show']);
Route::resource('contracts', ContractController::class)->only(['index', 'show']);
Route::resource('revenues', RevenueController::class);
Route::resource('expenses', ExpenseController::class)->only(['index', 'create', 'store', 'destroy']);

Route::get('cashbox', [CashboxController::class, 'index'])->name('cashbox.index');
Route::post('cashbox', [CashboxController::class, 'store'])->name('cashbox.store');
Route::get('debts', [DebtController::class, 'index'])->name('debts.index');
Route::get('remaining', [RemainingController::class, 'index'])->name('remaining.index');
Route::get('settlements', [SettlementController::class, 'index'])->name('settlements.index');
Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
