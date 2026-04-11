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

        View::composer('layouts.admin', function ($view): void {
            if ($pid = session('current_project_id')) {
                URL::defaults(['project' => $pid]);
            }

            $view->with('navProjects', Project::query()->listed()->orderBy('name')->get());
            $pidNav = session('current_project_id');
            $view->with(
                'navCurrentProject',
                $pidNav ? Project::query()->listed()->whereKey($pidNav)->first() : null
            );
        });
    }
}
