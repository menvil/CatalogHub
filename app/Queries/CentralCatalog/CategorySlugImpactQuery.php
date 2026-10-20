<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Data\CentralCatalog\CategorySlugImpactData;
use App\Data\CentralCatalog\CategorySlugImpactRoute;
use App\Domains\Projections\Builders\CategoryProjectionBuilder;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Enums\SiteDomainType;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteDomain;
use App\Models\SiteLocale;
use App\Models\SiteOverride;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Read-only routing-impact assessment for a canonical Category slug change (CA-018).
 *
 * Public Category routes resolve persisted SiteCategoryProjection slugs, never
 * central_categories.slug. A canonical change therefore reaches a Site only when
 * its owning sync workflow rebuilds the projection; nothing here writes,
 * schedules or invalidates projections, selections or overrides.
 */
final readonly class CategorySlugImpactQuery
{
    public const ROUTE_LIMIT = 12;

    public const SITE_LIMIT = 8;

    public function __construct(private CategoryProjectionBuilder $projections, private LocalizedUrlResolver $urls) {}

    public function assess(CentralCategory $category, string $currentSlug, string $proposedSlug): CategorySlugImpactData
    {
        $selected = Site::query()->selectingCategory($category->id)->orderBy('name')->orderBy('id')->get(['id', 'name', 'status']);
        $projections = SiteCategoryProjection::query()
            ->where('central_category_id', $category->id)
            ->whereHas('site')
            ->with(['site.locales.locale', 'site.domains'])
            ->orderBy('site_id')->orderBy('locale')->orderBy('id')
            ->get(['id', 'site_id', 'locale', 'slug', 'status', 'schema_version']);
        $overrides = SiteOverride::query()
            ->where('entity_type', 'category')->where('entity_id', $category->id)
            ->whereIn('field', ['slug', 'local_slug'])->where('status', 'active')
            ->orderBy('id')->get(['id', 'site_id', 'market_id', 'locale_code', 'field', 'value_json']);

        $ordered = $projections->sortBy([
            fn (SiteCategoryProjection $left, SiteCategoryProjection $right): int => [$this->site($left)->name, $left->locale, $left->id] <=> [$this->site($right)->name, $right->locale, $right->id],
        ])->values();
        $routes = $ordered->take(self::ROUTE_LIMIT)
            ->map(fn (SiteCategoryProjection $projection): CategorySlugImpactRoute => $this->route($category, $projection, $proposedSlug, $overrides))
            ->all();

        return new CategorySlugImpactData(
            categoryId: (int) $category->id,
            currentSlug: $currentSlug,
            proposedSlug: $proposedSlug,
            selectedSiteCount: $selected->count(),
            selectedSites: $selected->take(self::SITE_LIMIT)
                ->map(fn (Site $site): array => ['name' => $site->name, 'status' => $site->status->label()])->values()->all(),
            projectionCount: $projections->count(),
            currentSlugProjectionCount: $projections->where('slug', $currentSlug)->count(),
            servedRouteCount: $projections->filter(fn (SiteCategoryProjection $projection): bool => $this->notServedReason($projection) === null)->count(),
            routes: array_values($routes),
            hiddenRouteCount: max(0, $projections->count() - self::ROUTE_LIMIT),
            fingerprint: $this->fingerprint($category, $currentSlug, $proposedSlug, $selected, $projections, $overrides),
        );
    }

    /** @param Collection<int, SiteOverride> $overrides Active slug overrides of this Category. */
    private function route(CentralCategory $category, SiteCategoryProjection $projection, string $proposedSlug, Collection $overrides): CategorySlugImpactRoute
    {
        $site = $this->site($projection);
        // The projection builder owns override precedence; without any active Site or market
        // slug override row it necessarily falls back to the canonical slug.
        $overridable = $overrides->contains(fn (SiteOverride $override): bool => $override->site_id === null
            ? $override->market_id !== null && (int) $override->market_id === (int) $site->market_id
            : (int) $override->site_id === (int) $site->id);
        $afterSlug = $overridable ? $this->projections->effectiveSlug($site, $category, $projection->locale, $proposedSlug) : $proposedSlug;
        $reason = $this->notServedReason($projection);
        $trusted = $reason === null && $this->hostIsTrusted($site);

        return new CategorySlugImpactRoute(
            siteName: $site->name,
            siteStatus: $site->status->label(),
            locale: $projection->locale,
            projectionStatus: ucfirst($projection->status->value),
            servedNow: $reason === null,
            notServedReason: $reason,
            currentSlug: $projection->slug,
            currentPath: route('public.categories.show', ['locale' => $projection->locale, 'slug' => $projection->slug], false),
            currentUrl: $trusted ? $this->urls->category($site, $projection->locale, $projection) : null,
            slugAfterRebuild: $afterSlug,
            pathAfterRebuild: route('public.categories.show', ['locale' => $projection->locale, 'slug' => $afterSlug], false),
            urlAfterRebuild: $trusted ? $this->urls->category($site, $projection->locale, $afterSlug) : null,
            keptByOverride: $afterSlug !== $proposedSlug,
        );
    }

    /** Mirrors public resolution: schema 1 Active projection, public Site with a public domain, enabled active Locale. */
    private function notServedReason(SiteCategoryProjection $projection): ?string
    {
        $site = $this->site($projection);
        if (! $site->status->isPubliclyAvailable()) {
            return 'Site is '.$site->status->label();
        }
        if ($projection->status !== ProjectionStatus::Active || $projection->schema_version !== 1) {
            return 'Projection is '.ucfirst($projection->status->value);
        }
        if (! $site->domains->contains(fn (SiteDomain $domain): bool => $domain->is_active && in_array($domain->type, [SiteDomainType::Primary, SiteDomainType::Alias], true))) {
            return 'Site has no active public domain';
        }
        $locale = $site->locales->first(fn (SiteLocale $locale): bool => $locale->locale_code === $projection->locale);
        if (! $locale instanceof SiteLocale || ! $locale->is_enabled || ! (bool) $locale->locale?->is_active) {
            return 'Locale '.$projection->locale.' is not enabled for the Site';
        }

        return null;
    }

    /** LocalizedUrlResolver uses sites.domain; trust it only when it is an active public host of the Site. */
    private function hostIsTrusted(Site $site): bool
    {
        try {
            $host = SiteDomain::normalizeHost((string) $site->domain);
        } catch (InvalidArgumentException) {
            return false;
        }

        return $site->domains->contains(fn (SiteDomain $domain): bool => $domain->is_active && $domain->host === $host
            && in_array($domain->type, [SiteDomainType::Primary, SiteDomainType::Alias], true));
    }

    private function site(SiteCategoryProjection $projection): Site
    {
        $site = $projection->site;
        if (! $site instanceof Site) {
            throw new \LogicException('Projection Site was not loaded.');
        }

        return $site;
    }

    /**
     * @param  Collection<int, Site>  $selected
     * @param  Collection<int, SiteCategoryProjection>  $projections
     * @param  Collection<int, SiteOverride>  $overrides
     */
    private function fingerprint(CentralCategory $category, string $currentSlug, string $proposedSlug, Collection $selected, Collection $projections, Collection $overrides): string
    {
        $sites = $selected->mapWithKeys(fn (Site $site): array => [$site->id => $site->status->value])
            ->union($projections->mapWithKeys(fn (SiteCategoryProjection $projection): array => [$projection->site_id => $this->site($projection)->status->value]))
            ->sortKeys()->all();

        return hash('sha256', json_encode([
            'category' => (int) $category->id,
            'current' => $currentSlug,
            'proposed' => $proposedSlug,
            'selected' => $selected->pluck('id')->sort()->values()->all(),
            'sites' => $sites,
            'projections' => $projections->map(fn (SiteCategoryProjection $projection): array => [
                $projection->id, $projection->site_id, $projection->locale, $projection->slug, $projection->status->value, $projection->schema_version,
            ])->values()->all(),
            'overrides' => $overrides->map(fn (SiteOverride $override): array => [
                $override->id, $override->site_id, $override->market_id, $override->locale_code, $override->field, $override->overrideValue(),
            ])->values()->all(),
        ], JSON_THROW_ON_ERROR));
    }
}
