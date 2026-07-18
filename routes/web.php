<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApprovalsController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CashboxController;
use App\Http\Controllers\GlobalCashboxController;
use App\Http\Controllers\LandCashboxController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FacingController;
use App\Http\Controllers\LandController;
use App\Http\Controllers\LandTradingController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RemainingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\ShareholderController;
use App\Http\Controllers\ShareholderLedgerController;
use App\Http\Controllers\SiteSketchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CrmLeadController;
use App\Http\Controllers\TaskController;
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
        Route::get('projects/{project}/contract-template', [ProjectController::class, 'downloadContractTemplate'])->name('projects.contract-template');
        Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('projects/{managedProject}/draft', [ProjectController::class, 'toDraft'])->name('projects.draft');
        Route::post('projects/{draftProject}/restore', [ProjectController::class, 'restore'])->name('projects.restore');

        Route::get('projects/{project}', function (Project $project) {
            if ($project->is_draft) {
                return redirect()->route('projects.edit', $project);
            }

            return redirect()->route('properties.index', $project);
        })->name('projects.landing');

        Route::resource('users', UserController::class)->except(['show']);

        Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');

        // الصندوق الشامل (برا المشاريع) — مراقبة حركات كل المشاريع
        Route::get('global-cashbox', [GlobalCashboxController::class, 'index'])->name('global-cashbox.index');

        // صندوق أراضي البيع والشراء (مشروع نظامي) — يظهر أيضًا في الصندوق الشامل
        Route::get('land-cashbox', [LandCashboxController::class, 'index'])->name('land-cashbox.index');
        Route::post('land-cashbox', [LandCashboxController::class, 'store'])->name('land-cashbox.store');

        // إدارة المساهمين (برا المشاريع) — مساهم عام يمكن ربطه بعدة مشاريع
        Route::resource('shareholders', ShareholderController::class);
        Route::post('shareholders/{shareholder}/projects', [ShareholderController::class, 'attachProject'])
            ->name('shareholders.projects.attach');
        Route::post('shareholders/{shareholder}/lands', [ShareholderController::class, 'attachLand'])
            ->name('shareholders.lands.attach');
        Route::post('shareholders/{shareholder}/ledger', [ShareholderLedgerController::class, 'store'])
            ->name('shareholders.ledger.store');
        Route::delete('shareholders/{shareholder}/ledger/{ledger}', [ShareholderLedgerController::class, 'destroy'])
            ->whereNumber('ledger')
            ->name('shareholders.ledger.destroy');

        // CRM (برا المشاريع) - يعتمد على المشروع الحالي من السيشن
        Route::prefix('crm')->group(function (): void {
            Route::get('leads', [CrmLeadController::class, 'index'])->name('crm-leads.index');
            Route::get('leads/create', [CrmLeadController::class, 'create'])->name('crm-leads.create');
            Route::post('leads', [CrmLeadController::class, 'store'])->name('crm-leads.store');
            Route::get('leads/{lead}', [CrmLeadController::class, 'show'])->whereNumber('lead')->name('crm-leads.show');
            Route::get('leads/{lead}/edit', [CrmLeadController::class, 'edit'])->whereNumber('lead')->name('crm-leads.edit');
            Route::put('leads/{lead}', [CrmLeadController::class, 'update'])->whereNumber('lead')->name('crm-leads.update');
            Route::delete('leads/{lead}', [CrmLeadController::class, 'destroy'])->whereNumber('lead')->name('crm-leads.destroy');

            Route::post('leads/{lead}/activities', [CrmLeadController::class, 'storeActivity'])->whereNumber('lead')->name('crm-leads.activities.store');
        });

        // مخطط الموقع (كروكي العقارات) - برا المشاريع، لكن يقرأ بيانات كل المشاريع
        Route::prefix('site-sketch')->group(function (): void {
            Route::get('/', [SiteSketchController::class, 'index'])->name('site-sketch.index');
            Route::post('properties/{property}/cells', [SiteSketchController::class, 'updateCell'])
                ->whereNumber('property')
                ->name('site-sketch.cells.update');
            Route::post('properties/{property}/reset', [SiteSketchController::class, 'reset'])
                ->whereNumber('property')
                ->name('site-sketch.reset');
        });

        // أراضي البيع والشراء (برا المشاريع)
        Route::prefix('land-trading')->group(function (): void {
            Route::get('/', [LandTradingController::class, 'index'])->name('land-trading.index');
            Route::get('sales', [LandTradingController::class, 'sales'])->name('land-trading.sales');
            Route::get('create', [LandTradingController::class, 'create'])->name('land-trading.create');
            Route::post('/', [LandTradingController::class, 'store'])->name('land-trading.store');
            Route::post('{parcel}/payments', [LandTradingController::class, 'storePayment'])
                ->whereNumber('parcel')
                ->name('land-trading.payments.store');
            Route::delete('{parcel}/payments/{payment}', [LandTradingController::class, 'destroyPayment'])
                ->whereNumber('parcel')
                ->whereNumber('payment')
                ->name('land-trading.payments.destroy');
            Route::post('{parcel}/parts', [LandTradingController::class, 'storePart'])
                ->whereNumber('parcel')
                ->name('land-trading.parts.store');
            Route::put('{parcel}/parts/{part}', [LandTradingController::class, 'updatePart'])
                ->whereNumber('parcel')
                ->whereNumber('part')
                ->name('land-trading.parts.update');
            Route::delete('{parcel}/parts/{part}', [LandTradingController::class, 'destroyPart'])
                ->whereNumber('parcel')
                ->whereNumber('part')
                ->name('land-trading.parts.destroy');
            Route::get('{parcel}', [LandTradingController::class, 'show'])->whereNumber('parcel')->name('land-trading.show');
            Route::get('{parcel}/edit', [LandTradingController::class, 'edit'])->whereNumber('parcel')->name('land-trading.edit');
            Route::put('{parcel}', [LandTradingController::class, 'update'])->whereNumber('parcel')->name('land-trading.update');
            Route::delete('{parcel}', [LandTradingController::class, 'destroy'])->whereNumber('parcel')->name('land-trading.destroy');
        });

        // Tasks (برا المشاريع)
        Route::prefix('tasks')->group(function (): void {
            Route::get('/', [TaskController::class, 'index'])->name('tasks.index');
            Route::get('mine', [TaskController::class, 'mine'])->name('tasks.mine');
            Route::get('create', [TaskController::class, 'create'])->name('tasks.create');
            Route::post('/', [TaskController::class, 'store'])->name('tasks.store');
            Route::get('{task}', [TaskController::class, 'show'])->whereNumber('task')->name('tasks.show');
            Route::get('{task}/edit', [TaskController::class, 'edit'])->whereNumber('task')->name('tasks.edit');
            Route::put('{task}', [TaskController::class, 'update'])->whereNumber('task')->name('tasks.update');
            Route::delete('{task}', [TaskController::class, 'destroy'])->whereNumber('task')->name('tasks.destroy');

            Route::post('{task}/updates', [TaskController::class, 'storeUpdate'])->whereNumber('task')->name('tasks.updates.store');
            Route::get('{task}/updates/{update}/attachment', [TaskController::class, 'downloadUpdateAttachment'])
                ->whereNumber('task')
                ->whereNumber('update')
                ->name('tasks.updates.attachment');
        });
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
        Route::resource('sales', SaleController::class);
        Route::resource('clients', ClientController::class)->only(['index', 'show']);
        Route::get('contracts/{contract}/word', [ContractController::class, 'downloadWord'])->name('contracts.word');
        Route::resource('contracts', ContractController::class)->only(['index', 'show']);
        Route::resource('revenues', RevenueController::class);
        Route::resource('expenses', ExpenseController::class);

        Route::get('cashbox', [CashboxController::class, 'index'])->name('cashbox.index');
        Route::post('cashbox', [CashboxController::class, 'store'])->name('cashbox.store');
        Route::get('approvals', [ApprovalsController::class, 'index'])->name('approvals.index');
        Route::post('approvals/{type}/{id}/approve', [ApprovalsController::class, 'approve'])->whereNumber('id')->name('approvals.approve');
        Route::post('approvals/{type}/{id}/reject', [ApprovalsController::class, 'reject'])->whereNumber('id')->name('approvals.reject');
        Route::post('debts/{debt}/pay-from-cashbox', [DebtController::class, 'payFromCashbox'])->name('debts.pay-from-cashbox');
        Route::resource('debts', DebtController::class)->except(['show']);
        Route::get('remaining', [RemainingController::class, 'index'])->name('remaining.index');
        Route::get('settlements', [SettlementController::class, 'index'])->name('settlements.index');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
        Route::get('reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
        Route::get('reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('reports/export_excel', [ReportController::class, 'exportExcel'])->name('reports.export_excel');
        Route::get('reports/export_pdf', [ReportController::class, 'exportPdf'])->name('reports.export_pdf');
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/send-available-units-report', [SettingController::class, 'sendAvailableUnitsReportNow'])->name('settings.send-available-units-report');
    });
