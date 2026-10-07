<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Enums\UserRole;
use App\Filament\Resources\CentralCategoryResource;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Site;
use App\Models\User;
use App\Navigation\CentralNavigationRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CategoryListFixture;
use Tests\TestCase;

final class CentralCategoryListTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_route_has_one_registry_destination_and_no_competing_list(): void
    {
        CategoryListFixture::create();
        $actor = User::factory()->centralAdmin()->create();
        $this->actingAs($actor)->get('/admin/central/categories')->assertOk()
            ->assertSee('data-screen-id="CA-016"', false)->assertSee('data-fixture-version="categories-list-v1"', false)
            ->assertSee('Selected by Sites')->assertSee('Schema status')->assertSee('Category status')->assertSee('New Category')
            ->assertDontSee('Import Categories')->assertDontSee('type="checkbox"', false)->assertDontSee('Market / Language')->assertDontSee('from last month')->assertDontSee('3.1%');
        self::assertSame('/admin/central/categories', CentralCategoryResource::getUrl('index', isAbsolute: false));
        $this->get('/admin/central/central-categories?q=Cameras&level=0&per_page=50')
            ->assertRedirect('/admin/central/categories?q=Cameras&level=0&per_page=50');
        $items = app(CentralNavigationRegistry::class)->visibleItemsFor($actor);
        self::assertCount(1, array_filter($items, fn ($item) => $item['label'] === 'Categories'));
        self::assertFalse(CentralCategoryResource::shouldRegisterNavigation());
        $this->get(CentralCategoryResource::getUrl('create'))->assertOk();
        $this->get(CentralCategoryResource::getUrl('edit', ['record' => 194001]))->assertOk();
        $this->get(CentralCategoryResource::getUrl('schema', ['record' => 194001]))->assertOk();
        $locale = Locale::query()->first();
        $this->get(route('central.categories.translations.edit', [194001, $locale]))->assertOk();
        $this->get('/admin/central/categories/194001')->assertNotFound();
        $this->delete('/admin/central/categories/194001')->assertNotFound();
    }

    public function test_central_admin_and_category_editor_are_allowed_but_all_other_roles_are_denied(): void
    {
        foreach ([UserRole::CentralAdmin, UserRole::CatalogEditor] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/admin/central/categories')->assertOk();
        }
        foreach ([UserRole::SiteAdmin, UserRole::Moderator, UserRole::Translator] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->get('/admin/central/categories')->assertForbidden();
            self::assertSame([], array_values(array_filter(app(CentralNavigationRegistry::class)->visibleItemsFor($actor), fn ($item) => $item['id'] === 'categories')));
        }
        auth()->logout();
        $this->get('/admin/central/categories')->assertRedirect();
        $this->actingAs(User::factory()->centralAdmin()->disabled()->create())->get('/admin/central/categories')->assertForbidden();
    }

    public function test_wrong_capability_or_missing_panel_page_admission_fails_closed(): void
    {
        foreach ([['catalog.schema.manage'], ['catalog.products.manage', 'catalog.brands.manage'], ['catalog.categories.manage']] as $capabilities) {
            foreach ([['central.panel.access', 'central.page.access'], ['central.panel.access'], ['central.page.access']] as $admission) {
                if ($capabilities === ['catalog.categories.manage'] && count($admission) === 2) {
                    continue;
                }
                config(['cataloghub_permissions.roles.catalog_editor' => [...$admission, 'central.mutation.execute', ...$capabilities]]);
                $this->actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))->get('/admin/central/categories')->assertForbidden();
            }
        }
    }

    public function test_get_filters_sort_and_paging_never_change_any_database_table(): void
    {
        Locale::factory()->create(['code' => 'zz-ZZ', 'language_code' => 'zz', 'region_code' => 'ZZ', 'is_active' => false]);
        CategoryListFixture::create();
        $this->actingAs(User::factory()->centralAdmin()->create());
        $localeId = Locale::query()->active()->value('id');
        $before = $this->databaseSnapshot();
        $this->get('/admin/central/categories')->assertOk();
        $this->get('/admin/central/categories?q=Monitors&schema=draft&status=active&level=2&sort=products&direction=desc&locale='.$localeId.'&site=194010')->assertOk();
        $this->get('/admin/central/categories?page=2&per_page=20')->assertOk();
        self::assertSame($before, $this->databaseSnapshot());
    }

    public function test_read_only_category_actor_has_no_mutations_or_schema_link(): void
    {
        CategoryListFixture::create();
        config(['cataloghub_permissions.roles.catalog_editor' => ['central.panel.access', 'central.page.access', 'catalog.categories.manage']]);
        $actor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $this->actingAs($actor)->get('/admin/central/categories')->assertOk()->assertDontSee('New Category')->assertDontSee('role="menuitem"', false)->assertDontSee('confirm-form=', false);
        $before = $this->databaseSnapshot();
        foreach (['activate', 'archive', 'restore'] as $action) {
            $this->post('/admin/central/categories/194002/'.$action, ['confirmed' => 1])->assertForbidden();
        }
        self::assertSame($before, $this->databaseSnapshot());
        $this->get(CentralCategoryResource::getUrl('schema', ['record' => 194001]))->assertForbidden();
    }

    public function test_lifecycle_mutations_use_actions_confirmation_legal_states_and_retain_context(): void
    {
        CategoryListFixture::create();
        $this->actingAs(User::factory()->centralAdmin()->create());
        $path = '/admin/central/categories/194002';
        $this->post($path.'/activate?q=Coffee&sort=products&direction=desc&per_page=50')->assertRedirect('/admin/central/categories?q=Coffee&sort=products&direction=desc&per_page=50');
        $this->assertDatabaseHas('central_categories', ['id' => 194002, 'status' => 'active']);
        $this->post($path.'/archive')->assertRedirect()->assertSessionHasErrors('confirmed');
        $this->assertDatabaseHas('central_categories', ['id' => 194002, 'status' => 'active']);
        $this->post($path.'/archive', ['confirmed' => 1])->assertRedirect();
        $this->assertDatabaseHas('central_categories', ['id' => 194002, 'status' => 'archived']);
        $this->post($path.'/activate')->assertRedirect()->assertSessionHasErrors('status');
        $this->assertDatabaseHas('central_categories', ['id' => 194002, 'status' => 'archived']);
        $this->post($path.'/restore')->assertRedirect();
        $this->assertDatabaseHas('central_categories', ['id' => 194002, 'status' => 'draft']);
        $this->post($path.'/restore')->assertRedirect();
        self::assertSame(3, AuditLogEntry::query()->where('subject_id', 194002)->count());
        $this->post('/admin/central/categories/194001/restore')->assertSessionHasErrors('status');
        $this->assertDatabaseHas('central_categories', ['id' => 194001, 'status' => 'active']);
        $this->assertDatabaseHas('central_categories', ['id' => 194001, 'schema_status' => 'approved']);
    }

    #[DataProvider('invalidQueries')]
    public function test_invalid_parameters_fail_validation_safely(array $query): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create())->get(route('central.categories.index', $query))->assertRedirect()->assertSessionHasErrors(array_keys($query));
    }

    public static function invalidQueries(): array
    {
        return array_map(fn ($query) => [$query], [
            ['status' => 'needs_review'], ['schema' => 'ready'], ['locale' => 999999], ['locale' => 'en-US'], ['site' => 999999], ['level' => -1], ['sort' => 'bad'], ['direction' => 'backward'], ['per_page' => 10000], ['per_page' => 25], ['page' => -1], ['q' => ['bad']], ['level' => 'one'],
        ]);
    }

    public function test_inactive_locale_archived_deleted_sites_are_invalid_filters(): void
    {
        CategoryListFixture::create();
        Locale::query()->where('code', 'de-DE')->update(['is_active' => false]);
        $localeId = Locale::query()->where('code', 'de-DE')->value('id');
        $this->actingAs(User::factory()->centralAdmin()->create())->get('/admin/central/categories?locale='.$localeId)->assertRedirect()->assertSessionHasErrors('locale');
        $this->get('/admin/central/categories?site=194012')->assertRedirect()->assertSessionHasErrors('site');
        Site::findOrFail(194010)->delete();
        $this->get('/admin/central/categories?site=194010')->assertRedirect()->assertSessionHasErrors('site');
    }

    public function test_empty_filtered_empty_clear_query_and_paginator_state(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create())->get('/admin/central/categories')->assertOk()->assertSee('No categories yet')->assertSee('New Category')->assertDontSee('100%');
        CategoryListFixture::create();
        $response = $this->get('/admin/central/categories?q=no-match&sort=products&direction=desc&per_page=50');
        $response->assertOk()->assertSee('No matching categories')->assertSee('Clear filters');
        self::assertSame(route('central.categories.index', ['sort' => 'products', 'direction' => 'desc', 'per_page' => 50]), $response->viewData('clearFiltersUrl'));
        $response = $this->get('/admin/central/categories?status=active&sort=products&direction=desc&per_page=20');
        self::assertStringContainsString('status=active', $response->viewData('list')->categories->url(2));
        self::assertStringContainsString('sort=products', $response->viewData('list')->categories->url(2));
        $this->get('/admin/central/categories?page=2')->assertOk()->assertSee('Showing 21 to 25 of 25 categories');
        $response = $this->get('/admin/central/categories?status=active&page=2147483647');
        $response->assertOk()->assertSee('No categories on this page')->assertSee('First page')->assertDontSee('No categories yet');
        self::assertStringContainsString('status=active', $response->viewData('list')->categories->url(1));
    }

    public function test_render_query_count_is_bounded_from_one_to_twenty_five_rows(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create());
        CentralCategory::factory()->create();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get('/admin/central/categories?per_page=50')->assertOk();
        $one = count(DB::getQueryLog());
        DB::disableQueryLog();
        CentralCategory::factory()->count(24)->create();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get('/admin/central/categories?per_page=50')->assertOk();
        $many = count(DB::getQueryLog());
        DB::disableQueryLog();
        self::assertSame($one, $many);
        self::assertLessThanOrEqual(12, $many);
    }

    private function databaseSnapshot(): array
    {
        $snapshot = [];
        foreach (Schema::getTableListing() as $table) {
            $snapshot[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }

        return $snapshot;
    }
}
