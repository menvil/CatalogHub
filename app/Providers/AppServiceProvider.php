<?php

namespace App\Providers;

use App\Events\MarketOfferUpdated;
use App\Importers\SerializedPhpProductImporter;
use App\Listeners\RebuildPriceAffectedProjections;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use App\Observers\CentralProductObserver;
use App\Services\Imports\AttributeMappingService;
use App\Services\Imports\AttributeNormalizer;
use App\Services\Imports\DuplicateDetector;
use App\Services\Imports\ImportMediaDownloader;
use App\Services\Imports\ImportService;
use App\Services\Imports\Normalizers\BooleanNormalizer;
use App\Services\Imports\Normalizers\EnumNormalizer;
use App\Services\Imports\Normalizers\MultiEnumNormalizer;
use App\Services\Imports\Normalizers\NumberNormalizer;
use App\Services\Imports\Normalizers\UnitNormalizer;
use App\Services\Security\PublicRequestRateLimiter;
use App\Support\PermissionMatrix;
use App\View\Composers\PublicNavigationComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(AttributeMappingService::class);

        $this->app->singleton(
            ImportService::class,
            fn ($app): ImportService => new ImportService([
                $app->make(SerializedPhpProductImporter::class),
            ], $app->make(ImportMediaDownloader::class), $app->make(DuplicateDetector::class))
        );

        $this->app->singleton(
            AttributeNormalizer::class,
            fn ($app): AttributeNormalizer => new AttributeNormalizer([
                $app->make(BooleanNormalizer::class),
                $app->make(EnumNormalizer::class),
                $app->make(MultiEnumNormalizer::class),
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
        foreach (['public-reviews', 'public-leads', 'public-search', 'public-contact'] as $name) {
            $definition = PublicRequestRateLimiter::definition($name);

            RateLimiter::for(
                $name,
                fn (Request $request): Limit => Limit::perMinute($definition['max'])->by($request->ip()),
            );
        }

        Relation::morphMap([
            'central_product' => CentralProduct::class,
            'normalized_product_draft' => NormalizedProductDraft::class,
        ]);

        foreach (app(PermissionMatrix::class)->permissions() as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasCatalogHubPermission($permission));
        }

        CentralProduct::observe(CentralProductObserver::class);
        Event::listen(MarketOfferUpdated::class, RebuildPriceAffectedProjections::class);

        View::composer('public.partials.header', PublicNavigationComposer::class);
    }
}
