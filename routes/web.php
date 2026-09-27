<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApprovalsController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CashboxController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CrmLeadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\FacingController;
use App\Http\Controllers\FundTransferController;
use App\Http\Controllers\GlobalCashboxController;
use App\Http\Controllers\LandCashboxController;
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
use App\Http\Controllers\Site\SiteController;
use App\Http\Controllers\SiteSketchController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebsiteProjectController;
use App\Http\Controllers\WebsiteSettingsController;
use App\Http\Middleware\AuthorizeRoutePermission;
use App\Http\Middleware\SyncProjectFromRoute;
use App\Models\Project;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing site (root) — no auth
|--------------------------------------------------------------------------
*/
Route::name('site.')->group(function (): void {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/projects', [SiteController::class, 'projects'])->name('projects');
    Route::get('/projects/{project}', [SiteController::class, 'projectShow'])
        ->whereNumber('project')
        ->name('projects.show');
    Route::get('/about', [SiteController::class, 'about'])->name('about');
    Route::get('/contact', [SiteController::class, 'contact'])->name('contact');
    Route::post('/contact', [SiteController::class, 'contactStore'])->name('contact.store');
});

/*
|--------------------------------------------------------------------------
| Admin app (/app) — auth required
|--------------------------------------------------------------------------
*/
Route::prefix('app')->group(function (): void {
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
            Route::post('projects/{project}/adopt-plan', [ProjectController::class, 'adoptPlan'])->name('projects.adopt-plan');
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

            Route::get('global-cashbox', [GlobalCashboxController::class, 'index'])->name('global-cashbox.index');

            Route::get('approvals', [ApprovalsController::class, 'index'])->name('approvals.index');
            Route::post('approvals/{type}/{id}/approve', [ApprovalsController::class, 'approve'])->whereNumber('id')->name('approvals.approve');
            Route::post('approvals/{type}/{id}/reject', [ApprovalsController::class, 'reject'])->whereNumber('id')->name('approvals.reject');

            Route::get('land-cashbox', [LandCashboxController::class, 'index'])->name('land-cashbox.index');
            Route::post('land-cashbox', [LandCashboxController::class, 'store'])->name('land-cashbox.store');

            Route::get('fund-transfers', [FundTransferController::class, 'index'])->name('fund-transfers.index');
            Route::post('fund-transfers', [FundTransferController::class, 'store'])->name('fund-transfers.store');

            Route::resource('shareholders', ShareholderController::class);
            Route::post('shareholders/{shareholder}/projects', [ShareholderController::class, 'attachProject'])
                ->name('shareholders.projects.attach');
            Route::post('shareholders/{shareholder}/lands', [ShareholderController::class, 'attachLand'])
                ->name('shareholders.lands.attach');
            Route::put('shareholders/{shareholder}/funding', [ShareholderController::class, 'updateFunding'])
                ->name('shareholders.funding.update');
            Route::post('shareholders/{shareholder}/ledger', [ShareholderLedgerController::class, 'store'])
                ->name('shareholders.ledger.store');
            Route::post('shareholders/{shareholder}/ledger/{ledger}/allocate', [ShareholderLedgerController::class, 'allocate'])
                ->whereNumber('ledger')
                ->name('shareholders.ledger.allocate');
            Route::delete('shareholders/{shareholder}/ledger/{ledger}', [ShareholderLedgerController::class, 'destroy'])
                ->whereNumber('ledger')
                ->name('shareholders.ledger.destroy');

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

            // إدارة الموقع العام
            Route::prefix('website')->name('website.')->group(function (): void {
                Route::get('/', [WebsiteSettingsController::class, 'index'])->name('index');
                Route::put('brand', [WebsiteSettingsController::class, 'updateBrand'])->name('brand.update');

                Route::get('projects', [WebsiteProjectController::class, 'index'])->name('projects.index');
                Route::get('projects/{project}/edit', [WebsiteProjectController::class, 'edit'])->name('projects.edit');
                Route::put('projects/{project}', [WebsiteProjectController::class, 'update'])->name('projects.update');
                Route::post('projects/{project}/toggle', [WebsiteProjectController::class, 'quickToggle'])->name('projects.toggle');

                Route::get('{section}', [WebsiteSettingsController::class, 'edit'])
                    ->whereIn('section', ['general', 'home', 'about', 'trust', 'contact'])
                    ->name('edit');
                Route::put('{section}', [WebsiteSettingsController::class, 'update'])
                    ->whereIn('section', ['general', 'home', 'about', 'trust', 'contact'])
                    ->name('update');
            });

            Route::prefix('site-sketch')->group(function (): void {
                Route::get('/', [SiteSketchController::class, 'index'])->name('site-sketch.index');
                Route::post('properties/{property}/cells', [SiteSketchController::class, 'updateCell'])
                    ->whereNumber('property')
                    ->name('site-sketch.cells.update');
                Route::post('properties/{property}/reset', [SiteSketchController::class, 'reset'])
                    ->whereNumber('property')
                    ->name('site-sketch.reset');
            });

            Route::prefix('land-trading')->group(function (): void {
                Route::get('/', [LandTradingController::class, 'index'])->name('land-trading.index');
                Route::get('sales', [LandTradingController::class, 'sales'])->name('land-trading.sales');
                Route::get('create', [LandTradingController::class, 'create'])->name('land-trading.create');
                Route::post('/', [LandTradingController::class, 'store'])->name('land-trading.store');
                Route::post('{parcel}/payments', [LandTradingController::class, 'storePayment'])
                    ->whereNumber('parcel')
                    ->name('land-trading.payments.store');
                Route::post('{parcel}/payments/{payment}/distribute', [LandTradingController::class, 'distributePayment'])
                    ->whereNumber('parcel')
                    ->whereNumber('payment')
                    ->name('land-trading.payments.distribute');
                Route::delete('{parcel}/payments/{payment}', [LandTradingController::class, 'destroyPayment'])
                    ->whereNumber('parcel')
                    ->whereNumber('payment')
                    ->name('land-trading.payments.destroy');
                Route::post('{parcel}/adopt-plan', [LandTradingController::class, 'adoptPlan'])
                    ->whereNumber('parcel')
                    ->name('land-trading.adopt-plan');
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
            Route::resource('expense-types', ExpenseTypeController::class)->except(['show']);
            Route::resource('lands', LandController::class)->except(['show']);
            Route::resource('sales', SaleController::class);
            Route::resource('clients', ClientController::class)->only(['index', 'show']);
            Route::get('contracts/{contract}/word', [ContractController::class, 'downloadWord'])->name('contracts.word');
            Route::resource('contracts', ContractController::class)->only(['index', 'show']);
            Route::resource('revenues', RevenueController::class);
            Route::resource('expenses', ExpenseController::class);

            Route::get('cashbox', [CashboxController::class, 'index'])->name('cashbox.index');
            Route::post('cashbox', [CashboxController::class, 'store'])->name('cashbox.store');
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
});
