<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Project;
use App\Models\Property;
use App\Models\User;
use App\Policies\PropertyPolicy;
use App\Policies\UserPolicy;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use App\Repositories\Contracts\ShareholderRepositoryInterface;
use App\Repositories\Eloquent\PropertyRepository;
use App\Repositories\Eloquent\ShareholderRepository;
use App\Support\CurrentProject;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
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
        $this->app->singleton(CurrentProject::class, fn () => new CurrentProject);

        $this->app->bind(PropertyRepositoryInterface::class, PropertyRepository::class);
        $this->app->bind(ShareholderRepositoryInterface::class, ShareholderRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Paginator::useBootstrapFive();

        Gate::before(function (?User $user, string $ability): ?bool {
            if ($user instanceof User && $user->isAdmin()) {
                return true;
            }

            return null;
        });

        try {
            if (Schema::hasTable('permissions')) {
                foreach (Permission::query()->pluck('slug') as $slug) {
                    $slug = (string) $slug;
                    if (Gate::has($slug)) {
                        continue;
                    }
                    Gate::define($slug, fn (User $user): bool => $user->hasPermission($slug));
                }
            }
        } catch (\Throwable) {
            // تجاهل عند عدم توفر اتصال بقاعدة البيانات (مثلاً أثناء اختبارات بدون SQLite).
        }

        Route::bind('project', function (string $value): Project {
            return Project::query()
                ->whereKey($value)
                ->where('is_active', true)
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

        $projectSidebarActionsRaw = [
            ['route' => 'dashboard', 'label' => 'لوحة التحكم', 'icon' => 'fa-gauge-high', 'active' => ['dashboard'], 'permission' => 'dashboard.view'],
            [
                'route' => 'properties.index',
                'label' => 'العقارات',
                'icon' => 'fa-building',
                'active' => ['properties.index', 'properties.show', 'properties.edit'],
                'create_route' => 'properties.create',
                'create_active' => ['properties.create'],
                'permission' => 'properties.view',
                'create_permission' => 'properties.manage',
            ],
            ['route' => 'areas.index', 'label' => 'المناطق', 'icon' => 'fa-location-dot', 'active' => ['areas.*'], 'permission' => 'areas.manage'],
            ['route' => 'facings.index', 'label' => 'الوجهات', 'icon' => 'fa-compass-drafting', 'active' => ['facings.*'], 'permission' => 'facings.manage'],
            [
                'route' => 'lands.index',
                'label' => 'الأراضي',
                'icon' => 'fa-map-location-dot',
                'active' => ['lands.index', 'lands.edit'],
                'create_route' => 'lands.create',
                'create_active' => ['lands.create'],
                'permission' => 'lands.manage',
                'create_permission' => 'lands.manage',
            ],
            [
                'route' => 'shareholders.index',
                'label' => 'المساهمين',
                'icon' => 'fa-people-group',
                'active' => ['shareholders.*'],
                'create_route' => 'shareholders.create',
                'create_active' => ['shareholders.create'],
                'permission' => 'shareholders.view',
                'create_permission' => 'shareholders.manage',
            ],
            ['route' => 'clients.index', 'label' => 'العملاء', 'icon' => 'fa-users', 'active' => ['clients.*'], 'permission' => 'clients.view'],
            ['route' => 'contracts.index', 'label' => 'العقود', 'icon' => 'fa-file-signature', 'active' => ['contracts.*'], 'permission' => 'contracts.view'],
            [
                'route' => 'sales.index',
                'label' => 'المبيعات',
                'icon' => 'fa-cart-shopping',
                'active' => ['sales.index', 'sales.show', 'sales.edit'],
                'create_route' => 'sales.create',
                'create_active' => ['sales.create'],
                'permission' => 'sales.view',
                'create_permission' => 'sales.manage',
            ],
            [
                'route' => 'revenues.index',
                'label' => 'التحصيل',
                'icon' => 'fa-money-bill-trend-up',
                'active' => ['revenues.index', 'revenues.show', 'revenues.edit'],
                'create_route' => 'revenues.create',
                'create_active' => ['revenues.create'],
                'permission' => 'revenues.view',
                'create_permission' => 'revenues.manage',
            ],
            ['route' => 'cashbox.index', 'label' => 'الصندوق', 'icon' => 'fa-vault', 'active' => ['cashbox.*'], 'permission' => 'cashbox.view'],
            ['route' => 'approvals.index', 'label' => 'طلبات الاعتماد', 'icon' => 'fa-user-check', 'active' => ['approvals.*'], 'permission' => 'approvals.index'],
            [
                'route' => 'expenses.index',
                'label' => 'المصروفات',
                'icon' => 'fa-money-bill-wave',
                'active' => ['expenses.index'],
                'create_route' => 'expenses.create',
                'create_active' => ['expenses.create'],
                'permission' => 'expenses.view',
                'create_permission' => 'expenses.manage',
            ],
            [
                'route' => 'debts.index',
                'label' => 'ذمم دائنة',
                'icon' => 'fa-scale-balanced',
                'active' => ['debts.index', 'debts.edit'],
                'create_route' => 'debts.create',
                'create_active' => ['debts.create'],
                'permission' => 'debts.view',
                'create_permission' => 'debts.manage',
            ],
            ['route' => 'remaining.index', 'label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'active' => ['remaining.*'], 'permission' => 'remaining.view'],
            ['route' => 'settlements.index', 'label' => 'التصفيات', 'icon' => 'fa-filter-circle-dollar', 'active' => ['settlements.*'], 'permission' => 'settlements.view'],
            ['route' => 'reports.index', 'label' => 'التقارير', 'icon' => 'fa-chart-line', 'active' => ['reports.*'], 'permission' => 'reports.view'],
        ];

        View::composer('layouts.admin', function ($view) use ($projectSidebarActionsRaw): void {
            if ($pid = session('current_project_id')) {
                URL::defaults(['project' => $pid]);
            }

            $view->with('navProjects', Project::query()->listed()->orderBy('name')->get());
            $pidNav = session('current_project_id');
            $view->with(
                'navCurrentProject',
                $pidNav ? Project::query()->listed()->whereKey($pidNav)->first() : null
            );

            /** @var User|null $user */
            $user = Auth::user();
            $filtered = collect($projectSidebarActionsRaw)
                ->filter(function (array $action) use ($user): bool {
                    if (! $user instanceof User) {
                        return false;
                    }

                    return $user->can($action['permission']);
                })
                ->map(function (array $action) use ($user): array {
                    $createPerm = $action['create_permission'] ?? $action['permission'];
                    if (! empty($action['create_route']) && ! $user->can($createPerm)) {
                        $action['create_route'] = null;
                        $action['create_active'] = [];
                    }

                    return $action;
                })
                ->values()
                ->all();

            $view->with('projectSidebarActions', $filtered);
        });
    }
}
