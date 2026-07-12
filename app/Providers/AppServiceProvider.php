<?php

namespace App\Providers;

use App\Importers\SerializedPhpProductImporter;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\User;
use App\Observers\CentralProductObserver;
use App\Services\Imports\ImportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            ImportService::class,
            fn ($app): ImportService => new ImportService([
                $app->make(SerializedPhpProductImporter::class),
            ])
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('translations.manage', fn (User $user): bool => $user->hasCatalogHubPermission('translations.manage'));

        CentralProduct::observe(CentralProductObserver::class);
    }
}
