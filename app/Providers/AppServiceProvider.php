<?php

namespace App\Providers;

use App\Contracts\Auth\CentralAdminAccess;
use App\Contracts\Auth\LegacySiteAdminRouteAccess;
use App\Contracts\Auth\SiteAdminAccess;
use App\Events\MarketOfferUpdated;
use App\Importers\SerializedPhpProductImporter;
use App\Listeners\RebuildPriceAffectedProjections;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use App\Observers\CentralProductObserver;
use App\Policies\CentralPanelPolicy;
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
use App\Support\Auth\TemporaryLegacySiteAdminRouteAccess;
use App\Support\Auth\TemporarySiteAdminAccess;
use App\Support\PermissionMatrix;
use App\Support\Sites\SiteRuntimeContext;
use App\View\Composers\PublicNavigationComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use LogicException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CentralAdminAccess::class, CentralPanelPolicy::class);
        $this->app->bind(LegacySiteAdminRouteAccess::class, TemporaryLegacySiteAdminRouteAccess::class);
        $this->app->bind(SiteAdminAccess::class, TemporarySiteAdminAccess::class);

        $this->app->scoped(AttributeMappingService::class);
        $this->app->scoped(SiteRuntimeContext::class, function ($app): SiteRuntimeContext {
            $context = $app->make(Request::class)->attributes->get(SiteRuntimeContext::class);

            if (! $context instanceof SiteRuntimeContext) {
                throw new LogicException('Site runtime context is unavailable for this request.');
            }

            return $context;
        });

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

        Gate::define('system.super-admin', fn (User $user): bool => $user->isSuperAdmin());

        CentralProduct::observe(CentralProductObserver::class);
        Event::listen(MarketOfferUpdated::class, RebuildPriceAffectedProjections::class);

        View::composer('public.partials.header', PublicNavigationComposer::class);
    }
}
