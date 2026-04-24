<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Property;
use App\Policies\PropertyPolicy;
use App\Support\CurrentProject;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use App\Repositories\Contracts\ShareholderRepositoryInterface;
use App\Repositories\Eloquent\PropertyRepository;
use App\Repositories\Eloquent\ShareholderRepository;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentProject::class, fn() => new CurrentProject);

        $this->app->bind(PropertyRepositoryInterface::class, PropertyRepository::class);
        $this->app->bind(ShareholderRepositoryInterface::class, ShareholderRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Property::class, PropertyPolicy::class);
        Paginator::useBootstrapFive();

        Route::bind('project', function (string $value): Project {
            return Project::query()
                ->whereKey($value)
                ->where('is_active', true)
                ->where('is_draft', false)
                ->firstOrFail();
        });

        Route::bind('managedProject', function (string $value): Project {
            return Project::query()
                ->whereKey($value)
                ->where('is_active', true)
                ->where('is_draft', false)
                ->firstOrFail();
        });

        Route::bind('draftProject', function (string $value): Project {
            return Project::query()
                ->whereKey($value)
                ->where('is_draft', true)
                ->firstOrFail();
        });

        $projectSidebarActions = [
            ['route' => 'dashboard', 'label' => 'لوحة التحكم', 'icon' => 'fa-gauge-high', 'active' => ['dashboard']],
            [
                'route' => 'properties.index',
                'label' => 'العقارات',
                'icon' => 'fa-building',
                'active' => ['properties.index', 'properties.show', 'properties.edit'],
                'create_route' => 'properties.create',
                'create_active' => ['properties.create'],
            ],
            ['route' => 'areas.index', 'label' => 'المناطق', 'icon' => 'fa-location-dot', 'active' => ['areas.*']],
            ['route' => 'facings.index', 'label' => 'الوجهات', 'icon' => 'fa-compass-drafting', 'active' => ['facings.*']],
            [
                'route' => 'lands.index',
                'label' => 'الأراضي',
                'icon' => 'fa-map-location-dot',
                'active' => ['lands.index', 'lands.edit'],
                'create_route' => 'lands.create',
                'create_active' => ['lands.create'],
            ],
            ['route' => 'shareholders.index', 'label' => 'المساهمين', 'icon' => 'fa-people-group', 'active' => ['shareholders.*']],
            ['route' => 'clients.index', 'label' => 'العملاء', 'icon' => 'fa-users', 'active' => ['clients.*']],
            ['route' => 'contracts.index', 'label' => 'العقود', 'icon' => 'fa-file-signature', 'active' => ['contracts.*']],
            [
                'route' => 'sales.index',
                'label' => 'المبيعات',
                'icon' => 'fa-cart-shopping',
                'active' => ['sales.index', 'sales.show', 'sales.edit'],
                'create_route' => 'sales.create',
                'create_active' => ['sales.create'],
            ],
            [
                'route' => 'revenues.index',
                'label' => 'التحصيل',
                'icon' => 'fa-money-bill-trend-up',
                'active' => ['revenues.index', 'revenues.show', 'revenues.edit'],
                'create_route' => 'revenues.create',
                'create_active' => ['revenues.create'],
            ],
            ['route' => 'cashbox.index', 'label' => 'الصندوق', 'icon' => 'fa-vault', 'active' => ['cashbox.*']],
            [
                'route' => 'expenses.index',
                'label' => 'المصروفات',
                'icon' => 'fa-money-bill-wave',
                'active' => ['expenses.index'],
                'create_route' => 'expenses.create',
                'create_active' => ['expenses.create'],
            ],
            [
                'route' => 'debts.index',
                'label' => 'ذمم دائنة',
                'icon' => 'fa-scale-balanced',
                'active' => ['debts.index', 'debts.edit'],
                'create_route' => 'debts.create',
                'create_active' => ['debts.create'],
            ],
            ['route' => 'remaining.index', 'label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'active' => ['remaining.*']],
            ['route' => 'settlements.index', 'label' => 'التصفيات', 'icon' => 'fa-filter-circle-dollar', 'active' => ['settlements.*']],
            ['route' => 'reports.index', 'label' => 'التقارير', 'icon' => 'fa-chart-line', 'active' => ['reports.*']],
        ];

        View::composer('layouts.admin', function ($view) use ($projectSidebarActions): void {
            if ($pid = session('current_project_id')) {
                URL::defaults(['project' => $pid]);
            }

            $view->with('navProjects', Project::query()->listed()->orderBy('name')->get());
            $pidNav = session('current_project_id');
            $view->with(
                'navCurrentProject',
                $pidNav ? Project::query()->listed()->whereKey($pidNav)->first() : null
            );
            $view->with('projectSidebarActions', $projectSidebarActions);
        });
    }
}
