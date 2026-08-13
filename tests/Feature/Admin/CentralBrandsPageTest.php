<?php

namespace Tests\Feature\Admin;

use App\Data\CentralCatalog\CentralBrandListFiltersData;
use App\Enums\CentralBrandStatus;
use App\Enums\UserRole;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Navigation\CentralNavigationRegistry;
use App\Queries\CentralCatalog\CentralBrandListQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CentralBrandsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_opens_the_single_canonical_brands_page(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $brand = CentralBrand::factory()->create(['name' => 'Acme Displays']);

        $this->actingAs($admin)
            ->get(route('central.brands.index'))
            ->assertOk()
            ->assertSee('data-admin-layout="central"', false)
            ->assertSee('data-screen-id="CA-011"', false)
            ->assertSee('data-central-header-breadcrumbs', false)
            ->assertSee('Brands')
            ->assertSee('Manage brand profiles, product associations, media assets, and localization across your catalog.')
            ->assertSee($brand->name)
            ->assertSee('data-ui-select', false)
            ->assertSee('aria-sort="ascending"', false)
            ->assertDontSee('>Filters</span>', false)
            ->assertDontSeeText('CA-011');
    }

    public function test_brand_page_filters_by_search_and_status(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $visible = CentralBrand::factory()->create([
            'name' => 'Matching Brand',
            'status' => CentralBrandStatus::Active,
        ]);
        CentralBrand::factory()->create([
            'name' => 'Matching Archived',
            'status' => CentralBrandStatus::Archived,
        ]);
        CentralBrand::factory()->create([
            'name' => 'Different Brand',
            'status' => CentralBrandStatus::Active,
        ]);

        $this->actingAs($admin)
            ->get(route('central.brands.index', [
                'search' => 'Matching',
                'status' => CentralBrandStatus::Active->value,
            ]))
            ->assertOk()
            ->assertSee($visible->name)
            ->assertDontSee('Matching Archived')
            ->assertDontSee('Different Brand');
    }

    public function test_brand_list_pagination_is_stable(): void
    {
        $brands = CentralBrand::factory()->count(3)->create([
            'name' => 'Same Name',
        ]);
        $query = app(CentralBrandListQuery::class);
        $filters = new CentralBrandListFiltersData(null, null);

        $first = $query->paginate($filters, perPage: 2, page: 1)->getCollection()->pluck('id')->all();
        $second = $query->paginate($filters, perPage: 2, page: 2)->getCollection()->pluck('id')->all();

        $this->assertSame($brands->pluck('id')->all(), [...$first, ...$second]);
    }

    public function test_brand_page_sorts_by_name_and_status(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        CentralBrand::factory()->create(['name' => 'Alpha', 'status' => CentralBrandStatus::Active]);
        CentralBrand::factory()->create(['name' => 'Zulu', 'status' => CentralBrandStatus::Archived]);

        $nameResponse = $this->actingAs($admin)->get(route('central.brands.index', [
            'sort' => 'name',
            'direction' => 'desc',
        ]));
        $nameResponse->assertOk()->assertSeeInOrder(['Zulu', 'Alpha']);

        $statusResponse = $this->actingAs($admin)->get(route('central.brands.index', [
            'sort' => 'status',
            'direction' => 'asc',
        ]));
        $statusResponse->assertOk()->assertSeeInOrder(['Alpha', 'Zulu']);
    }

    public function test_brand_page_rejects_invalid_filters_and_per_page_values(): void
    {
        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($admin)
            ->get(route('central.brands.index', [
                'status' => 'unknown',
                'sort' => 'unsafe',
                'direction' => 'sideways',
                'per_page' => 500,
            ]))
            ->assertSessionHasErrors(['status', 'sort', 'direction', 'per_page']);
    }

    public function test_brands_navigation_points_to_the_canonical_page(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $brands = collect(app(CentralNavigationRegistry::class)->visibleItemsFor($admin))
            ->firstWhere('id', 'brands');

        $this->assertIsArray($brands);
        $this->assertSame(route('central.brands.index', absolute: false), $brands['url']);
    }

    public function test_user_without_catalog_permission_cannot_open_brands(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->get(route('central.brands.index'))
            ->assertForbidden();
    }
}
