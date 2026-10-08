<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\UserRole;
use App\Filament\Resources\CentralCategoryResource;
use App\Http\Requests\CentralAdmin\CentralCategoryDetailRequest;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\User;
use App\Navigation\CentralNavigationRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CategoryDetailFixture;
use Tests\TestCase;

final class CentralCategoryDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_id_route_identity_hierarchy_schema_and_real_destinations(): void
    {
        $category = CategoryDetailFixture::create();
        $this->actingAs(User::factory()->centralAdmin()->create());
        $response = $this->get(route('central.categories.show', $category))->assertOk()
            ->assertSee('data-screen-id="CA-017"', false)->assertSee('data-fixture-version="category-detail-v1"', false)
            ->assertSee('Gaming Monitors')->assertSee('Current revision')->assertSee('Configured comparison attributes')
            ->assertSee('Ada Catalog')->assertSee('Sam Schema')->assertSee('Selected by Sites')
            ->assertSee('Archive Category')->assertDontSee('Restore Category')->assertDontSee('Activate Category');
        $response->assertSee(route('central.categories.show', 195002), false)
            ->assertSee(CentralCategoryResource::getUrl('edit', ['record' => $category]), false)
            ->assertSee(CentralCategoryResource::getUrl('schema', ['record' => $category]), false);
        foreach (['Duplicate Category', 'Export Category', 'Comparison Sets', 'Facet Sets', 'Channels', 'View Full Activity', 'Published on Sites', 'Add Attribute', 'Add Facet', 'Approve Schema'] as $unsupported) {
            $response->assertDontSee($unsupported);
        }
        $this->get('/admin/central/categories/'.$category->slug)->assertNotFound();
        $this->get('/admin/central/categories/99999999')->assertNotFound();
        $this->delete(route('central.categories.show', $category))->assertStatus(405);
        $this->get(CentralCategoryResource::getUrl('edit', ['record' => $category]))->assertOk();
        $this->get(CentralCategoryResource::getUrl('schema', ['record' => $category]))->assertOk();
    }

    public function test_categories_list_identity_opens_detail_without_a_second_navigation_item(): void
    {
        $category = CentralCategory::factory()->create();
        $actor = User::factory()->centralAdmin()->create();
        $this->actingAs($actor)->get(route('central.categories.index'))->assertOk()->assertSee(route('central.categories.show', $category), false);
        self::assertCount(1, array_filter(app(CentralNavigationRegistry::class)->visibleItemsFor($actor), fn ($item) => $item['label'] === 'Categories'));
    }

    public function test_all_roles_disabled_and_capability_admission_boundaries(): void
    {
        $category = CentralCategory::factory()->create();
        foreach ([UserRole::CentralAdmin, UserRole::CatalogEditor] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('central.categories.show', $category))->assertOk();
        }
        foreach ([UserRole::SiteAdmin, UserRole::Moderator, UserRole::Translator] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('central.categories.show', $category))->assertForbidden();
        }
        $this->actingAs(User::factory()->centralAdmin()->disabled()->create())->get(route('central.categories.show', $category))->assertForbidden();
        foreach ([['central.panel.access', 'central.page.access', 'catalog.schema.manage'], ['central.panel.access', 'central.page.access', 'catalog.products.manage'], ['central.panel.access', 'central.page.access', 'catalog.brands.manage'], ['catalog.categories.manage', 'central.panel.access'], ['catalog.categories.manage', 'central.page.access']] as $permissions) {
            config(['cataloghub_permissions.roles.catalog_editor' => $permissions]);
            $this->actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))->get(route('central.categories.show', $category))->assertForbidden();
        }
        auth()->logout();
        $this->get(route('central.categories.show', $category))->assertRedirect();
    }

    public function test_read_only_category_actor_has_no_mutations_schema_translation_or_site_navigation(): void
    {
        $category = CategoryDetailFixture::create();
        config(['cataloghub_permissions.roles.catalog_editor' => ['central.panel.access', 'central.page.access', 'catalog.categories.manage']]);
        $this->actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]));
        $this->get(route('central.categories.show', $category))->assertOk()->assertDontSee('Edit Category')->assertDontSee('View Schema')->assertDontSee('Edit en-US translation')->assertDontSee('Archive Category')->assertDontSee('href="/admin/sites/', false);
        $before = $this->snapshot();
        $this->post(route('central.categories.archive', $category), ['context' => 'detail', 'confirmed' => 1])->assertForbidden();
        self::assertSame($before, $this->snapshot());
        $this->get(CentralCategoryResource::getUrl('schema', ['record' => $category]))->assertForbidden();
        $this->get(route('central.categories.translations.edit', [$category, Locale::query()->where('code', 'en-US')->value('id')]))->assertForbidden();
    }

    public function test_locale_context_fallback_and_owning_translation_route(): void
    {
        $category = CategoryDetailFixture::create();
        $de = Locale::query()->where('code', 'de-DE')->firstOrFail();
        $this->actingAs(User::factory()->centralAdmin()->create());
        $this->get(route('central.categories.show', ['category' => $category, 'locale' => $de->id]))->assertOk()
            ->assertSee('Showing en-US fallback')->assertSee('de-DE translation is missing')->assertSee('3/5 covered')->assertSee('Edit de-DE translation')
            ->assertSee(route('central.categories.translations.edit', [$category, $de]), false);
        $this->get(route('central.categories.translations.edit', [$category, $de]))->assertOk();
        $this->from(route('central.categories.show', $category))->get(route('central.categories.show', ['category' => $category, 'locale' => 999999]))->assertSessionHasErrors('locale');
    }

    public function test_locale_can_become_inactive_after_request_validation_and_reject_safely(): void
    {
        $category = CentralCategory::factory()->create();
        $locale = Locale::factory()->create(['is_active' => true]);
        $this->app->afterResolving(CentralCategoryDetailRequest::class, function (CentralCategoryDetailRequest $request) use ($locale): void {
            self::assertSame($locale->id, $request->localeId());
            $locale->update(['is_active' => false]);
        });
        $this->actingAs(User::factory()->centralAdmin()->create())->from(route('central.categories.show', $category))
            ->get(route('central.categories.show', ['category' => $category, 'locale' => $locale->id]))
            ->assertRedirect(route('central.categories.show', $category))->assertSessionHasErrors('locale')->assertDontSee('data-screen-id="CA-017"', false);
    }

    public function test_get_and_locale_switch_do_not_modify_any_database_table_or_audit(): void
    {
        $category = CategoryDetailFixture::create();
        $this->actingAs(User::factory()->centralAdmin()->create());
        $before = $this->snapshot();
        $this->get(route('central.categories.show', $category))->assertOk();
        foreach (Locale::query()->active()->pluck('id') as $id) {
            $this->get(route('central.categories.show', ['category' => $category, 'locale' => $id]))->assertOk();
        }
        self::assertSame($before, $this->snapshot());
    }

    public function test_empty_and_no_active_locale_states_are_honest(): void
    {
        $category = CentralCategory::factory()->create(['status' => 'draft', 'schema_status' => 'draft']);
        $this->actingAs(User::factory()->centralAdmin()->create())->get(route('central.categories.show', $category))->assertOk()
            ->assertSee('No active Locales')->assertSee('No recorded Category activity')->assertSee('No eligible Sites')
            ->assertSee('Not recorded')->assertSee('Activate Category')->assertDontSee('100%')->assertDontSee('0% quality');
        $locale = Locale::factory()->create(['is_active' => true]);
        $this->get(route('central.categories.show', ['category' => $category, 'locale' => $locale->id]))->assertOk()->assertSee('No localized description')->assertSee('0/1 covered');
    }

    public function test_detail_lifecycle_returns_to_same_id_preserves_locale_and_updates_the_list(): void
    {
        $category = CentralCategory::factory()->create(['status' => 'draft']);
        $locale = Locale::factory()->create(['is_active' => true]);
        $this->actingAs(User::factory()->centralAdmin()->create());
        foreach ([['activate', 'draft', 'active'], ['archive', 'active', 'archived'], ['restore', 'archived', 'draft']] as [$command, $before, $after]) {
            $this->post(route('central.categories.'.$command, $category), ['context' => 'detail', 'expected_status' => $before, 'confirmed' => $command === 'archive' ? 1 : null, 'locale' => $locale->id, 'return_url' => 'https://untrusted.test'])
                ->assertRedirect(route('central.categories.show', ['category' => $category, 'locale' => $locale->id]));
            self::assertSame($after, $category->fresh()->status->value);
            $this->get(route('central.categories.show', $category))->assertOk()->assertSee(ucfirst($after));
            $this->get(route('central.categories.index', ['q' => $category->slug]))->assertOk()->assertSee(ucfirst($after));
        }
        self::assertSame(3, AuditLogEntry::query()->count());
        $this->post(route('central.categories.activate', $category), ['expected_status' => 'draft'])->assertRedirect(route('central.categories.index'));
        $this->get(route('central.categories.show', $category))->assertOk()->assertSee('Category:')->assertSee('Active');
    }

    public function test_confirmation_illegal_and_stale_status_fail_without_changes_or_audit(): void
    {
        $category = CentralCategory::factory()->create(['status' => 'active']);
        $this->actingAs(User::factory()->centralAdmin()->create());
        $before = $this->snapshot();
        $this->from(route('central.categories.show', $category));
        $this->post(route('central.categories.archive', $category), ['context' => 'detail'])->assertSessionHasErrors('confirmed');
        $this->post(route('central.categories.restore', $category), ['context' => 'detail'])->assertSessionHasErrors('status');
        $this->post(route('central.categories.archive', $category), ['context' => 'detail', 'confirmed' => 1, 'expected_status' => 'draft'])->assertSessionHasErrors('status');
        self::assertSame($before, $this->snapshot());
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
