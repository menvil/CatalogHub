<?php

namespace App\Providers;

use App\Models\CentralCatalog\CentralProduct;
use App\Observers\CentralProductObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CentralProduct::observe(CentralProductObserver::class);
    }
}
