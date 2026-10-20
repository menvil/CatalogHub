<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsCategorySaveData;
use Tests\Support\CategoryCreateEditFixture as Fixture;
use Tests\TestCase;

/** CA-018 canonical slug routing-impact preview, acknowledgement and staleness. */
final class CentralCategorySlugImpactTest extends TestCase
{
    use BuildsCategorySaveData, RefreshDatabase;

    private CentralCategory $monitors;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitors = Fixture::create();
        $this->actingAs(User::factory()->centralAdmin()->create());
    }

    public function test_unchanged_slug_needs_no_preview_or_acknowledgement(): void
    {
        $this->get(route('central.categories.slug-impact', [$this->monitors, 'slug' => 'desktop-monitors']))->assertOk()->assertDontSee('Routing impact');
        $this->patch(route('central.categories.update', $this->monitors), $this->categoryUpdatePayload($this->monitors, ['name' => 'Displays & Monitors']))
            ->assertSessionHasNoErrors();
        self::assertSame('desktop-monitors', $this->monitors->fresh()->slug);
    }

    public function test_changed_slug_without_review_redirects_to_a_real_server_side_impact_preview(): void
    {
        $before = $this->facts();
        $payload = [...$this->categoryUpdatePayload($this->monitors), 'slug' => 'displays-monitors'];
        $this->patch(route('central.categories.update', $this->monitors), $payload)
            ->assertRedirect(route('central.categories.edit', [$this->monitors, 'slug_preview' => 'displays-monitors']))
            ->assertSessionHasErrors(['slug_impact' => 'Review the routing impact of the slug change before saving.']);
        $afterRejectedSave = $this->facts();

        $this->get(route('central.categories.edit', [$this->monitors, 'slug_preview' => 'displays-monitors']))->assertOk()
            ->assertSee('Routing impact of this slug change')
            ->assertSee('data-impact-current-slug>desktop-monitors', false)->assertSee('data-impact-proposed-slug>displays-monitors', false)
            ->assertSee('<code>'.Fixture::MONITORS.'</code> — unchanged', false)
            ->assertSee('Display World · Active, Preview Outlet · Draft')
            ->assertSee('2 · 1 use the current slug · 2 served now')
            ->assertSee('https://display-world.test/en-US/categories/desktop-monitors')
            ->assertSee('https://display-world.test/en-US/categories/displays-monitors')
            ->assertSee('unchanged — a Site or market slug override keeps <code>bildschirme</code>', false)
            ->assertSee('No automatic HTTP redirect is created.')
            ->assertSee('Existing routes keep serving their current slug until the owning Site sync rebuilds the Category projection')
            ->assertSee('name="slug_impact_fingerprint"', false)->assertSee('name="slug_impact_acknowledged"', false);
        self::assertSame([$before, $before], [$afterRejectedSave, $this->facts()]);
    }

    public function test_meaningful_slug_change_requires_acknowledgement_then_saves_without_redirects_or_projection_writes(): void
    {
        $before = $this->facts();
        $this->patch(route('central.categories.update', $this->monitors), $this->categoryUpdatePayload($this->monitors, ['slug' => 'displays-monitors']))
            ->assertSessionHasErrors(['slug_impact_acknowledged']);
        self::assertSame($before, $this->facts());

        $this->patch(route('central.categories.update', $this->monitors), $this->categoryUpdatePayload($this->monitors, ['slug' => 'displays-monitors', 'acknowledged' => true]))
            ->assertSessionHasNoErrors()->assertRedirect(route('central.categories.show', Fixture::MONITORS));
        self::assertSame(['displays-monitors', Fixture::MONITORS], [$this->monitors->fresh()->slug, $this->monitors->fresh()->id]);
        $after = $this->facts();
        // Routes, selections, overrides and jobs are owned elsewhere: nothing but the canonical row and its audit changed.
        foreach (['projections', 'selections', 'overrides', 'jobs', 'sitemaps', 'search'] as $owned) {
            self::assertSame($before[$owned], $after[$owned], $owned);
        }
        self::assertSame([], array_values(array_filter(Schema::getTableListing(), fn (string $table): bool => str_contains($table, 'redirect'))));
        $audit = AuditLogEntry::query()->where('action', 'catalog.category.updated')->sole();
        self::assertSame(['slug' => 'desktop-monitors'], $audit->before_json);
        self::assertSame(['slug' => 'displays-monitors', 'changed_fields' => ['slug']], $audit->after_json);
    }

    public function test_slug_change_without_selections_or_projections_needs_the_preview_but_no_acknowledgement(): void
    {
        $smartHome = CentralCategory::query()->findOrFail(Fixture::SMART_HOME);
        $this->get(route('central.categories.slug-impact', [$smartHome, 'slug' => 'connected-home']))->assertOk()
            ->assertSee('data-impact-selected-sites>0', false)->assertSee('0 · 0 use the current slug · 0 served now')
            ->assertSee('no confirmation is required')->assertDontSee('name="slug_impact_acknowledged"', false);
        $this->patch(route('central.categories.update', $smartHome), $this->categoryUpdatePayload($smartHome, ['slug' => 'connected-home']))->assertSessionHasNoErrors();
        self::assertSame('connected-home', $smartHome->fresh()->slug);
    }

    public function test_a_stale_or_forged_reviewed_impact_cannot_be_saved(): void
    {
        $reviewed = $this->categoryUpdatePayload($this->monitors, ['slug' => 'displays-monitors', 'acknowledged' => true]);
        $outlet = Site::factory()->withRuntimeContext(['en-US'])->create(['name' => 'Outlet Three', 'code' => 'ca018-outlet-three', 'domain' => 'outlet-three.test', 'status' => 'active']);
        SiteCategory::query()->create(['site_id' => $outlet->id, 'central_category_id' => Fixture::MONITORS, 'is_enabled' => true]);
        $before = $this->facts();
        $this->patch(route('central.categories.update', $this->monitors), $reviewed)
            ->assertRedirect(route('central.categories.edit', [$this->monitors, 'slug_preview' => 'displays-monitors']))
            ->assertSessionHasErrors(['slug_impact' => 'The routing impact changed after it was reviewed. Review the updated impact and confirm again.']);
        self::assertSame($before, $this->facts());

        $this->patch(route('central.categories.update', $this->monitors), [...$reviewed, 'slug_impact_fingerprint' => str_repeat('a', 64)])->assertSessionHasErrors(['slug_impact']);
        $this->patch(route('central.categories.update', $this->monitors), array_diff_key($reviewed, ['slug_impact_fingerprint' => true]))->assertSessionHasErrors(['slug_impact']);
        DB::table('site_category_projections')->where('central_category_id', Fixture::MONITORS)->where('locale', 'en-US')->update(['status' => 'stale']);
        $refreshed = $this->categoryUpdatePayload($this->monitors, ['slug' => 'displays-monitors', 'acknowledged' => true]);
        DB::table('site_category_projections')->where('central_category_id', Fixture::MONITORS)->where('locale', 'en-US')->update(['status' => 'active']);
        $this->patch(route('central.categories.update', $this->monitors), $refreshed)->assertSessionHasErrors(['slug_impact']);
        self::assertSame('desktop-monitors', $this->monitors->fresh()->slug);
        self::assertSame(0, AuditLogEntry::query()->where('action', 'catalog.category.updated')->count());
    }

    public function test_a_stale_impact_rolls_back_an_already_executed_reparent(): void
    {
        $reviewed = $this->categoryUpdatePayload($this->monitors, ['slug' => 'displays-monitors', 'acknowledged' => true, 'parent_id' => Fixture::ELECTRONICS]);
        SiteCategory::query()->where('site_id', Fixture::PREVIEW_OUTLET)->delete();
        $before = $this->facts();
        $this->patch(route('central.categories.update', $this->monitors), $reviewed)->assertSessionHasErrors(['slug_impact']);
        self::assertSame($before, $this->facts());
        self::assertSame(Fixture::DISPLAYS, $this->monitors->fresh()->parent_id);
        self::assertSame(0, AuditLogEntry::query()->where('action', 'catalog.category.reparented')->where('subject_id', (string) Fixture::MONITORS)->count());
    }

    /**
     * @return array<string, string>
     *
     * @phpstan-impure
     */
    private function facts(): array
    {
        $rows = fn (string $table): string => DB::table($table)->orderBy('id')->get()->toJson();

        return [
            'categories' => DB::table('central_categories')->orderBy('id')->get()->toJson(),
            'scopes' => DB::table('category_hierarchy_scopes')->orderBy('scope_key')->get()->toJson(),
            'audit' => $rows('audit_log_entries'),
            'projections' => $rows('site_category_projections'),
            'selections' => $rows('site_categories'),
            'overrides' => $rows('site_overrides'),
            'jobs' => $rows('projection_jobs'),
            'sitemaps' => $rows('site_sitemap_urls'),
            'search' => $rows('site_search_documents'),
        ];
    }
}
