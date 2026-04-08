<?php

namespace App\Providers;

use App\Models\Property;
use App\Policies\PropertyPolicy;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use App\Repositories\Contracts\ShareholderRepositoryInterface;
use App\Repositories\Eloquent\PropertyRepository;
use App\Repositories\Eloquent\ShareholderRepository;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
    }
}
