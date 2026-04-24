<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CashboxController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FacingController;
use App\Http\Controllers\LandController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RemainingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\ShareholderController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthorizeRoutePermission;
use App\Http\Middleware\SyncProjectFromRoute;
use App\Models\Project;
use Illuminate\Support\Facades\Route;

$modules = [
    'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
    'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
    'facings' => ['label' => 'الوجهات', 'icon' => 'fa-compass-drafting', 'route' => 'facings.index'],
    'lands' => ['label' => 'الأراضي', 'icon' => 'fa-map-location-dot', 'route' => 'lands.index'],
    'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
    'properties' => ['label' => 'عقارات', 'icon' => 'fa-building', 'route' => 'properties.index'],
    'clients' => ['label' => 'عملاء', 'icon' => 'fa-users', 'route' => 'clients.index'],
    'contracts' => ['label' => 'العقود', 'icon' => 'fa-file-signature', 'route' => 'contracts.index'],
    'sales' => ['label' => 'المبيعات', 'icon' => 'fa-cart-shopping', 'route' => 'sales.index'],
    'revenues' => ['label' => 'ايرادات', 'icon' => 'fa-money-bill-trend-up', 'route' => 'revenues.index'],
    'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'cashbox.index'],
    'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'expenses.index'],
    'debts' => ['label' => 'ذمم دائنة', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
    'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
    'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
    'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
    'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'remaining.index'],
];

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware(AuthorizeRoutePermission::class)->group(function (): void {
        Route::get('/', function () {
            $pid = session('current_project_id') ?? Project::query()->listed()->orderBy('id')->value('id');
            if ($pid === null) {
                return redirect()->route('projects.index');
            }

            return redirect()->route('properties.index', ['project' => $pid]);
        })->name('home');

        Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('projects/{managedProject}/draft', [ProjectController::class, 'toDraft'])->name('projects.draft');
        Route::post('projects/{draftProject}/restore', [ProjectController::class, 'restore'])->name('projects.restore');

        Route::resource('users', UserController::class)->except(['show']);
    });
});

Route::middleware(['auth', AuthorizeRoutePermission::class, SyncProjectFromRoute::class])
    ->prefix('{project}')
    ->scopeBindings()
    ->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('properties', PropertyController::class);
        Route::resource('areas', AreaController::class)->except(['show']);
        Route::resource('facings', FacingController::class)->except(['show']);
        Route::resource('lands', LandController::class)->except(['show']);
        Route::resource('shareholders', ShareholderController::class);
        Route::resource('sales', SaleController::class);
        Route::resource('clients', ClientController::class)->only(['index', 'show']);
        Route::resource('contracts', ContractController::class)->only(['index', 'show']);
        Route::resource('revenues', RevenueController::class);
        Route::resource('expenses', ExpenseController::class)->only(['index', 'create', 'store', 'destroy']);

        Route::get('cashbox', [CashboxController::class, 'index'])->name('cashbox.index');
        Route::post('cashbox', [CashboxController::class, 'store'])->name('cashbox.store');
        Route::post('debts/{debt}/pay-from-cashbox', [DebtController::class, 'payFromCashbox'])->name('debts.pay-from-cashbox');
        Route::resource('debts', DebtController::class)->except(['show']);
        Route::get('remaining', [RemainingController::class, 'index'])->name('remaining.index');
        Route::get('settlements', [SettlementController::class, 'index'])->name('settlements.index');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
