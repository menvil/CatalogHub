<?php

namespace App\Providers;

use App\Importers\SerializedPhpProductImporter;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\User;
use App\Observers\CentralProductObserver;
use App\Services\Imports\AttributeNormalizer;
use App\Services\Imports\ImportService;
use App\Services\Imports\Normalizers\BooleanNormalizer;
use App\Services\Imports\Normalizers\NumberNormalizer;
use App\Services\Imports\Normalizers\UnitNormalizer;
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

        $this->app->singleton(
            AttributeNormalizer::class,
            fn ($app): AttributeNormalizer => new AttributeNormalizer([
                $app->make(BooleanNormalizer::class),
                $app->make(UnitNormalizer::class),
                $app->make(NumberNormalizer::class),
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
