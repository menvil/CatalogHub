<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Data\CentralCatalog\CategorySlugImpactData;
use App\Data\CentralCatalog\CategorySlugImpactRoute;
use App\Domains\Projections\SiteSyncService;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteLocale;
use App\Queries\CentralCatalog\CategorySlugImpactQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CategoryCreateEditFixture as Fixture;
use Tests\TestCase;

final class CategorySlugImpactQueryTest extends TestCase
{
    use RefreshDatabase;

    private CentralCategory $monitors;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitors = Fixture::create();
    }

    public function test_assessment_reports_real_selections_projections_overrides_and_trusted_urls(): void
    {
        $impact = $this->assess();
        self::assertSame([Fixture::MONITORS, 'desktop-monitors', 'displays-monitors'], [$impact->categoryId, $impact->currentSlug, $impact->proposedSlug]);
        self::assertSame([2, [['name' => 'Display World', 'status' => 'Active'], ['name' => 'Preview Outlet', 'status' => 'Draft']]], [$impact->selectedSiteCount, $impact->selectedSites]);
        self::assertSame([2, 1, 2, 0], [$impact->projectionCount, $impact->currentSlugProjectionCount, $impact->servedRouteCount, $impact->hiddenRouteCount]);
        self::assertTrue($impact->requiresAcknowledgement());
        [$german, $english] = $impact->routes;
        self::assertSame(['de-DE', 'bildschirme', 'bildschirme', true, false], [$german->locale, $german->currentSlug, $german->slugAfterRebuild, $german->keptByOverride, $german->changesOnRebuild()]);
        self::assertSame('https://display-world.test/de-DE/categories/bildschirme', $german->currentUrl);
        self::assertSame(['en-US', true, null, '/en-US/categories/desktop-monitors', '/en-US/categories/displays-monitors'], [$english->locale, $english->servedNow, $english->notServedReason, $english->currentPath, $english->pathAfterRebuild]);
        self::assertSame(['https://display-world.test/en-US/categories/desktop-monitors', 'https://display-world.test/en-US/categories/displays-monitors'], [$english->currentUrl, $english->urlAfterRebuild]);
        self::assertFalse($english->keptByOverride);
        self::assertSame(64, strlen($impact->fingerprint));
    }

    public function test_routes_are_not_presented_as_served_or_linked_unless_public_resolution_would_serve_them(): void
    {
        DB::table('site_category_projections')->where('locale', 'de-DE')->update(['status' => 'stale']);
        self::assertSame('Projection is Stale', $this->route('de-DE')->notServedReason);
        self::assertNull($this->route('de-DE')->currentUrl);

        DB::table('site_category_projections')->where('locale', 'de-DE')->update(['status' => 'active']);
        SiteLocale::query()->where('site_id', Fixture::DISPLAY_WORLD)->where('locale_code', 'de-DE')->update(['is_enabled' => false]);
        self::assertSame('Locale de-DE is not enabled for the Site', $this->route('de-DE')->notServedReason);
        SiteLocale::query()->where('site_id', Fixture::DISPLAY_WORLD)->update(['is_enabled' => true]);

        // A sites.domain value that is not an active public host is never turned into an absolute URL.
        Site::query()->whereKey(Fixture::DISPLAY_WORLD)->update(['domain' => 'unverified.example']);
        self::assertTrue($this->route('en-US')->servedNow);
        self::assertNull($this->route('en-US')->currentUrl);
        self::assertSame('/en-US/categories/desktop-monitors', $this->route('en-US')->currentPath);

        Site::query()->whereKey(Fixture::DISPLAY_WORLD)->update(['status' => 'suspended']);
        self::assertSame('Site is Suspended', $this->route('en-US')->notServedReason);
        self::assertSame(0, $this->assess()->servedRouteCount);
    }

    public function test_deleted_and_archived_sites_are_excluded_like_the_ca016_and_ca017_selection_definition(): void
    {
        $archived = Site::factory()->create(['name' => 'Archived Outlet', 'code' => 'ca018-archived', 'domain' => 'archived.test', 'status' => 'archived']);
        SiteCategory::query()->create(['site_id' => $archived->id, 'central_category_id' => Fixture::MONITORS]);
        self::assertSame(2, $this->assess()->selectedSiteCount);
        Site::query()->findOrFail(Fixture::DISPLAY_WORLD)->delete();
        $impact = $this->assess();
        self::assertSame([1, 0, []], [$impact->selectedSiteCount, $impact->projectionCount, $impact->routes]);
    }

    public function test_fingerprint_is_stable_but_changes_with_any_reviewed_fact(): void
    {
        $fingerprint = $this->assess()->fingerprint;
        self::assertSame($fingerprint, $this->assess()->fingerprint);
        self::assertNotSame($fingerprint, $this->assess('other-slug')->fingerprint);
        $changes = [
            fn () => SiteCategory::query()->where('site_id', Fixture::PREVIEW_OUTLET)->delete(),
            fn () => DB::table('site_category_projections')->where('locale', 'en-US')->update(['slug' => 'monitor-screens']),
            fn () => DB::table('site_overrides')->update(['value_json' => json_encode(['value' => 'monitore'])]),
            fn () => Site::query()->whereKey(Fixture::DISPLAY_WORLD)->update(['status' => 'suspended']),
        ];
        foreach ($changes as $change) {
            $change();
            $next = $this->assess()->fingerprint;
            self::assertNotSame($fingerprint, $next);
            $fingerprint = $next;
        }
    }

    public function test_assessment_is_read_only_and_bounded(): void
    {
        $site = Site::factory()->withRuntimeContext(['en-US', 'de-DE', 'fr-FR', 'it-IT', 'es-ES', 'nl-NL', 'pl-PL'])->create(['name' => 'Wide Store', 'code' => 'ca018-wide', 'domain' => 'wide.test', 'status' => 'active']);
        $second = Site::factory()->withRuntimeContext(['en-US', 'de-DE', 'fr-FR', 'it-IT', 'es-ES', 'nl-NL', 'pl-PL'])->create(['name' => 'Wider Store', 'code' => 'ca018-wider', 'domain' => 'wider.test', 'status' => 'active']);
        foreach ([$site, $second] as $store) {
            foreach (['en-US', 'de-DE', 'fr-FR', 'it-IT', 'es-ES', 'nl-NL', 'pl-PL'] as $locale) {
                app(SiteSyncService::class)->syncCategory($store, $this->monitors->refresh(), $locale);
            }
        }
        $before = $this->snapshot();
        DB::enableQueryLog();
        $impact = $this->assess();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();
        self::assertSame($before, $this->snapshot());
        self::assertSame([16, CategorySlugImpactQuery::ROUTE_LIMIT, 4], [$impact->projectionCount, count($impact->routes), $impact->hiddenRouteCount]);
        // Seven reads plus at most eight override lookups for each displayed route whose Site or market has a slug override.
        self::assertLessThanOrEqual(7 + 8 * 2, $queries);
    }

    private function assess(string $proposed = 'displays-monitors'): CategorySlugImpactData
    {
        return app(CategorySlugImpactQuery::class)->assess($this->monitors->refresh(), 'desktop-monitors', $proposed);
    }

    private function route(string $locale): CategorySlugImpactRoute
    {
        foreach ($this->assess()->routes as $route) {
            if ($route->locale === $locale) {
                return $route;
            }
        }
        self::fail('Route not found for '.$locale);
    }

    /** @return array<string, list<string>> */
    private function snapshot(): array
    {
        $snapshot = [];
        foreach (Schema::getTableListing() as $table) {
            $rows = DB::table($table)->get()->map(fn ($row): string => json_encode($row, JSON_THROW_ON_ERROR))->all();
            sort($rows);
            $snapshot[$table] = $rows;
        }

        return $snapshot;
    }
}
