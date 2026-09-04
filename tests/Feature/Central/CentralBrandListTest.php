<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use App\Data\CentralCatalog\BrandListFiltersData;
use App\Data\CentralCatalog\CentralBrandListRow;
use App\Enums\CentralBrandStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Queries\CentralCatalog\CentralBrandListQuery;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\BrandListFixture;
use Tests\Support\CountryReference;
use Tests\TestCase;

final class CentralBrandListTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_list_pagination_is_stable_when_primary_sort_values_are_tied(): void
    {
        $brands = CentralBrand::factory()->count(3)->active()->create();
        $filters = new BrandListFiltersData(
            search: null,
            status: null,
            countryId: null,
            categoryCoverage: null,
            translation: null,
            quality: null,
            sort: 'status',
            direction: 'asc',
            perPage: 2,
        );
        $query = app(CentralBrandListQuery::class);

        $first = $query->paginate($filters, page: 1)->getCollection()->pluck('id')->all();
        $second = $query->paginate($filters, page: 2)->getCollection()->pluck('id')->all();

        $this->assertSame($brands->pluck('id')->values()->all(), [...$first, ...$second]);
        $this->assertSame([], array_values(array_intersect($first, $second)));
    }

    public function test_ca_011_uses_the_canonical_brand_list_presentation(): void
    {
        $brand = CentralBrand::factory()->active()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'country_id' => CountryReference::id('KR'),
            'website_url' => 'https://www.samsung.com/global/long-path',
            'updated_at' => CarbonImmutable::parse('2026-08-13T09:00:00Z'),
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.index'))
            ->assertOk()
            ->assertSee('data-admin-layout="central"', false)
            ->assertSee('data-screen-id="CA-011"', false)
            ->assertSee('data-central-header-breadcrumbs', false)
            ->assertSee('class="brand-list-filters"', false)
            ->assertSee('data-admin-data-table', false)
            ->assertSee('data-admin-status-badge="success"', false)
            ->assertSee('Brands')
            ->assertSee('Manage brand profiles, product associations, media assets, and localization across your catalog.')
            ->assertSeeInOrder(['Brand', 'Category Coverage', 'Products', 'Status', 'Translation Coverage', 'Quality', 'Updated', 'Actions'])
            ->assertDontSee('Logo Health')
            ->assertSee('New Brand')
            ->assertSee('href="'.route('central.brands.create', absolute: false).'"', false)
            ->assertSee('Samsung')
            ->assertSee('samsung')
            ->assertSee('Active')
            ->assertSee('data-row-id="'.$brand->getKey().'"', false)
            ->assertSee('data-admin-row-actions="'.$brand->getKey().'"', false)
            ->assertSee('href="'.route('central.brands.show', $brand, absolute: false).'"', false)
            ->assertSee('View')
            ->assertSee('href="'.route('central.brands.edit', $brand, absolute: false).'"', false)
            ->assertSee('Edit')
            ->assertDontSee('Import Brands')
            ->assertDontSee('>Sites<', false)
            ->assertDontSee('type="checkbox"', false)
            ->assertDontSee('href="https://www.samsung.com/global/long-path"', false);
    }

    public function test_search_by_name_and_slug_is_database_backed_and_preserves_constraints(): void
    {
        CentralBrand::factory()->create(['name' => 'Samsung Electronics', 'slug' => 'samsung-electronics']);
        CentralBrand::factory()->create(['name' => 'Sony', 'slug' => 'sony']);
        CentralBrand::factory()->create(['name' => 'Logitech', 'slug' => 'gaming-slug']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('central.brands.index', ['q' => 'samsung']))
            ->assertOk()
            ->assertSee('Samsung Electronics')
            ->assertDontSee('Sony')
            ->assertDontSee('Logitech');

        $this->get(route('central.brands.index', ['q' => 'gaming-slug']))
            ->assertOk()
            ->assertSee('Logitech')
            ->assertDontSee('Samsung Electronics')
            ->assertDontSee('Sony');

        $this->get(route('central.brands.index'))
            ->assertOk()
            ->assertSee('Samsung Electronics')
            ->assertSee('Sony')
            ->assertSee('Logitech');
    }

    public function test_untrusted_legacy_website_value_is_never_rendered_as_an_executable_link(): void
    {
        CentralBrand::factory()->create([
            'name' => 'Legacy Brand',
            'slug' => 'legacy-brand',
            'website_url' => 'javascript:alert(1)',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.index'))
            ->assertOk()
            ->assertSee('Legacy Brand')
            ->assertDontSee('href="javascript:alert(1)"', false);
    }

    public function test_status_filter_returns_each_lifecycle_state_and_clear_restores_all(): void
    {
        CentralBrand::factory()->draft()->create(['name' => 'Draft Brand', 'slug' => 'draft-brand']);
        CentralBrand::factory()->active()->create(['name' => 'Active Brand', 'slug' => 'active-brand']);
        CentralBrand::factory()->archived()->create(['name' => 'Archived Brand', 'slug' => 'archived-brand']);
        $this->actingAs(User::factory()->create());

        foreach ([
            CentralBrandStatus::Draft->value => ['Draft Brand', 'Active Brand', 'Archived Brand'],
            CentralBrandStatus::Active->value => ['Active Brand', 'Draft Brand', 'Archived Brand'],
            CentralBrandStatus::Archived->value => ['Archived Brand', 'Draft Brand', 'Active Brand'],
        ] as $status => [$visible, $hiddenA, $hiddenB]) {
            $this->get(route('central.brands.index', ['status' => $status]))
                ->assertOk()
                ->assertSee($visible)
                ->assertDontSee($hiddenA)
                ->assertDontSee($hiddenB);
        }

        $this->get(route('central.brands.index'))
            ->assertSee('Draft Brand')
            ->assertSee('Active Brand')
            ->assertSee('Archived Brand');
    }

    public function test_global_clear_resets_every_filter_and_preserves_only_presentation_state(): void
    {
        CentralBrand::factory()->create(['name' => 'Filtered Brand', 'slug' => 'filtered-brand']);
        $this->actingAs(User::factory()->create());
        $clearUrl = route('central.brands.index', [
            'sort' => 'products',
            'direction' => 'desc',
            'per_page' => 50,
        ], absolute: false);

        $this->get(route('central.brands.index', [
            'q' => 'Filtered',
            'country' => CountryReference::id('US'),
            'status' => CentralBrandStatus::Active->value,
            'coverage' => 'none',
            'translation' => 'missing',
            'quality' => 'needs_attention',
            'sort' => 'products',
            'direction' => 'desc',
            'per_page' => 50,
            'page' => 4,
        ]))
            ->assertOk()
            ->assertSee('data-brand-active-filter-count="6"', false)
            ->assertSee('6 active filters')
            ->assertSee('Clear filters')
            ->assertSee($clearUrl)
            ->assertDontSee('aria-label="Clear Country"', false)
            ->assertDontSee('data-ui-searchable-select-clear', false);

        $this->get($clearUrl)
            ->assertOk()
            ->assertSee('Filtered Brand')
            ->assertDontSee('Clear filters')
            ->assertDontSee('data-brand-active-filter-count', false);
    }

    public function test_sorting_is_case_insensitive_stable_and_supports_updated_order(): void
    {
        CentralBrand::factory()->create(['name' => 'Acer', 'slug' => 'acer', 'updated_at' => '2026-08-02 09:00:00']);
        CentralBrand::factory()->create(['name' => 'ASUS', 'slug' => 'asus', 'updated_at' => '2026-08-03 09:00:00']);
        CentralBrand::factory()->create(['name' => 'Zulu', 'slug' => 'zulu', 'updated_at' => '2026-08-01 09:00:00']);
        $this->actingAs(User::factory()->create());

        $this->get(route('central.brands.index'))
            ->assertSeeInOrder(['Acer', 'ASUS', 'Zulu']);

        $this->get(route('central.brands.index', ['sort' => 'name', 'direction' => 'desc']))
            ->assertSeeInOrder(['Zulu', 'ASUS', 'Acer']);

        $this->get(route('central.brands.index', ['sort' => 'updated_at', 'direction' => 'asc']))
            ->assertSeeInOrder(['Zulu', 'Acer', 'ASUS']);
    }

    public function test_pagination_is_bounded_and_has_no_page_overlap(): void
    {
        BrandListFixture::create();
        $this->actingAs(User::factory()->create());

        $this->get(route('central.brands.index'))
            ->assertOk()
            ->assertSee('Acer')
            ->assertSee('Samsung')
            ->assertDontSee('Sony')
            ->assertDontSee('Xiaomi')
            ->assertSee('Showing 1 to 20')
            ->assertSee('20 per page')
            ->assertSee('50 per page')
            ->assertSee('100 per page');

        $this->get(route('central.brands.index', ['page' => 2]))
            ->assertOk()
            ->assertDontSee('Acer')
            ->assertDontSee('Samsung')
            ->assertSeeInOrder(['Sony', 'ViewSonic', 'Xiaomi', 'Zotac'])
            ->assertSee('Showing 21 to 24');
    }

    public function test_requested_page_sizes_are_cast_and_passed_to_pagination(): void
    {
        BrandListFixture::create();
        $this->actingAs(User::factory()->create());

        foreach ([50, 100] as $perPage) {
            $response = $this->get(route('central.brands.index', ['per_page' => (string) $perPage]))
                ->assertOk();

            /** @var LengthAwarePaginator<int, CentralBrandListRow> $brands */
            $brands = $response->viewData('brands');

            $this->assertSame($perPage, $brands->perPage());
        }
    }

    public function test_database_and_filtered_empty_states_are_clear(): void
    {
        $createUrl = route('central.brands.create', absolute: false);

        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.index'))
            ->assertOk()
            ->assertSee('data-ui-screen-state="empty"', false)
            ->assertSee('No brands yet')
            ->assertSee('Create the first canonical brand in the central catalog.')
            ->assertSee('New Brand')
            ->assertSee('href="'.$createUrl.'"', false)
            ->assertSeeInOrder(['No brands yet', 'New Brand']);

        $emptyResponse = $this->get(route('central.brands.index'));
        $emptyResponse->assertSeeInOrder(['Total Brands', '0', 'Active', '0', 'With Logos', '0']);
        $emptyResponse->assertDontSee('NaN');

        CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        $this->get(route('central.brands.index', ['q' => 'zzzz-not-existing-brand']))
            ->assertOk()
            ->assertSee('data-ui-screen-state="filtered-empty"', false)
            ->assertSee('No matching brands')
            ->assertSee('No brands match the current search and filters.')
            ->assertSee('Clear filters')
            ->assertDontSee('Create the first canonical brand in the central catalog.');
    }

    public function test_brand_list_exposes_create_view_and_edit_actions_and_no_legacy_routes(): void
    {
        $brand = CentralBrand::factory()->create();
        $this->actingAs(User::factory()->create())
            ->get(route('central.brands.index'))
            ->assertOk()
            ->assertSee('New Brand')
            ->assertSee('View')
            ->assertSee('Edit')
            ->assertSee('href="'.route('central.brands.show', $brand, absolute: false).'"', false)
            ->assertDontSee('Archive Brand')
            ->assertDontSee('Activate Brand')
            ->assertDontSee('Restore Brand')
            ->assertDontSee('Delete Brand');

        $this->assertFalse(Route::has('filament.central.resources.brands.index'));
        $this->assertFalse(Route::has('filament.central.resources.brands.create'));
        $this->assertFalse(Route::has('filament.central.resources.brands.edit'));
        $this->get('/admin/central/brands/create')->assertOk();
        $this->get("/admin/central/brands/{$brand->getKey()}/edit")->assertOk();
        $this->get('/admin/central/central-brands/create')->assertNotFound();
    }

    public function test_user_without_catalog_permission_cannot_open_ca_011(): void
    {
        $translator = User::factory()->create(['role' => UserRole::Translator]);

        $this->actingAs($translator)
            ->get(route('central.brands.index'))
            ->assertForbidden();
    }

    public function test_product_management_permission_does_not_open_ca_011(): void
    {
        config()->set('cataloghub_permissions.roles.catalog_editor', [
            Permission::CentralPanelAccess->value,
            Permission::CatalogProductsManage->value,
        ]);
        $productManager = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($productManager)
            ->get(route('central.brands.index'))
            ->assertForbidden();
    }

    public function test_invalid_list_parameters_are_rejected_without_querying_unbounded_data(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('central.brands.index', ['per_page' => 100]))
            ->assertOk()
            ->assertSessionDoesntHaveErrors();

        $this->get(route('central.brands.index', ['per_page' => 101]))
            ->assertSessionHasErrors(['per_page']);

        $this->get(route('central.brands.index', [
            'status' => 'deleted',
            'country' => 999999,
            'coverage' => 'manual',
            'translation' => 'published',
            'quality' => 'needs_review',
            'sort' => 'normalized_name_hash',
            'direction' => 'sideways',
            'per_page' => 1000,
        ]))
            ->assertSessionHasErrors(['status', 'country', 'coverage', 'translation', 'quality', 'sort', 'direction', 'per_page']);
    }
}
