<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\CentralBrandStatus;
use App\Enums\UserRole;
use App\Filament\Resources\CentralBrandResource;
use App\Filament\Resources\CentralBrandResource\Pages\ListCentralBrands;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Support\BrandListFixture;
use Tests\TestCase;

final class CentralBrandResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ca_011_is_the_only_registered_brand_resource_page(): void
    {
        $this->assertSame(CentralBrand::class, CentralBrandResource::getModel());
        $this->assertSame(['index'], array_keys(CentralBrandResource::getPages()));
        $this->assertSame('http://localhost/admin/central/brands', CentralBrandResource::getUrl('index'));
        $this->assertTrue(class_exists(ListCentralBrands::class));
        $this->assertFalse(Route::has('filament.central.resources.brands.create'));
        $this->assertFalse(Route::has('filament.central.resources.brands.edit'));
    }

    public function test_authorized_catalog_user_can_open_ca_011_and_see_the_production_columns(): void
    {
        $brand = CentralBrand::factory()->active()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'country_code' => 'KR',
            'website_url' => 'https://www.samsung.com/global/long-path',
            'updated_at' => CarbonImmutable::parse('2026-08-13T09:00:00Z'),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(CentralBrandResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Brands')
            ->assertSee('Canonical brands used across the central catalog.')
            ->assertSee('Samsung')
            ->assertSee('samsung')
            ->assertSee('Active')
            ->assertSee('KR')
            ->assertSee('samsung.com')
            ->assertSee('Aug 13, 2026')
            ->assertSee('href="https://www.samsung.com/global/long-path" target="_blank"', escape: false);

        Livewire::actingAs($user)
            ->test(ListCentralBrands::class)
            ->assertCanSeeTableRecords([$brand])
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('slug')
            ->assertTableColumnExists('status')
            ->assertTableColumnExists('country_code')
            ->assertTableColumnExists('website_url')
            ->assertTableColumnExists('updated_at')
            ->assertTableFilterExists('status');
    }

    public function test_search_by_name_and_slug_hides_unrelated_records_and_can_be_cleared(): void
    {
        $samsung = CentralBrand::factory()->create(['name' => 'Samsung Electronics', 'slug' => 'samsung-electronics']);
        $sony = CentralBrand::factory()->create(['name' => 'Sony', 'slug' => 'sony']);
        $logitech = CentralBrand::factory()->create(['name' => 'Logitech', 'slug' => 'gaming-slug']);

        Livewire::actingAs(User::factory()->create())
            ->test(ListCentralBrands::class)
            ->searchTable('samsung')
            ->assertCanSeeTableRecords([$samsung])
            ->assertCanNotSeeTableRecords([$sony, $logitech])
            ->searchTable('gaming-slug')
            ->assertCanSeeTableRecords([$logitech])
            ->assertCanNotSeeTableRecords([$samsung, $sony])
            ->searchTable()
            ->assertCanSeeTableRecords([$samsung, $sony, $logitech]);
    }

    public function test_status_filter_returns_each_lifecycle_state_and_clears_to_all(): void
    {
        $draft = CentralBrand::factory()->draft()->create();
        $active = CentralBrand::factory()->active()->create();
        $archived = CentralBrand::factory()->archived()->create();
        $component = Livewire::actingAs(User::factory()->create())->test(ListCentralBrands::class);

        foreach ([
            CentralBrandStatus::Draft->value => $draft,
            CentralBrandStatus::Active->value => $active,
            CentralBrandStatus::Archived->value => $archived,
        ] as $status => $visible) {
            $hidden = collect([$draft, $active, $archived])->reject(fn (CentralBrand $brand): bool => $brand->is($visible));

            $component
                ->filterTable('status', $status)
                ->assertCanSeeTableRecords([$visible])
                ->assertCanNotSeeTableRecords($hidden);
        }

        $component
            ->resetTableFilters()
            ->assertCanSeeTableRecords([$draft, $active, $archived]);
    }

    public function test_default_and_interactive_sorts_are_deterministic(): void
    {
        $alphaOlder = CentralBrand::factory()->create([
            'name' => 'Alpha One',
            'slug' => 'alpha-older',
            'updated_at' => '2026-08-01 09:00:00',
        ]);
        $alphaNewer = CentralBrand::factory()->create([
            'name' => 'Alpha Two',
            'slug' => 'alpha-newer',
            'updated_at' => '2026-08-03 09:00:00',
        ]);
        $zulu = CentralBrand::factory()->create([
            'name' => 'Zulu',
            'slug' => 'zulu',
            'updated_at' => '2026-08-02 09:00:00',
        ]);
        $component = Livewire::actingAs(User::factory()->create())->test(ListCentralBrands::class);

        $component
            ->assertCanSeeTableRecords([$alphaOlder, $alphaNewer, $zulu], inOrder: true)
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords([$zulu, $alphaNewer, $alphaOlder], inOrder: true)
            ->sortTable('updated_at', 'asc')
            ->assertCanSeeTableRecords([$alphaOlder, $zulu, $alphaNewer], inOrder: true);
    }

    public function test_name_sort_uses_canonical_case_insensitive_identity(): void
    {
        $acer = CentralBrand::factory()->create(['name' => 'Acer', 'slug' => 'acer']);
        $asus = CentralBrand::factory()->create(['name' => 'ASUS', 'slug' => 'asus']);

        Livewire::actingAs(User::factory()->create())
            ->test(ListCentralBrands::class)
            ->assertCanSeeTableRecords([$acer, $asus], inOrder: true)
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords([$asus, $acer], inOrder: true);
    }

    public function test_default_pagination_is_twenty_and_second_page_has_no_overlap(): void
    {
        $records = BrandListFixture::create()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        $this->assertCount(24, $records);
        $firstPage = $records->take(20);
        $secondPage = $records->skip(20);
        $component = Livewire::actingAs(User::factory()->create())->test(ListCentralBrands::class);

        $component
            ->assertSet('tableRecordsPerPage', 20)
            ->assertSeeHtml('<option value="20">')
            ->assertSeeHtml('<option value="50">')
            ->assertSeeHtml('<option value="100">')
            ->assertDontSeeHtml('<option value="all">')
            ->assertCanSeeTableRecords($firstPage)
            ->assertCanNotSeeTableRecords($secondPage)
            ->call('gotoPage', 2)
            ->assertCanSeeTableRecords($secondPage)
            ->assertCanNotSeeTableRecords($firstPage);
    }

    public function test_database_and_filtered_empty_states_use_distinct_copy(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ListCentralBrands::class)
            ->assertSee('No brands yet')
            ->assertSee('Canonical brands will appear here once they are created.');

        CentralBrand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        Livewire::actingAs($user)
            ->test(ListCentralBrands::class)
            ->searchTable('zzzz-not-existing-brand')
            ->assertSee('No brands match your current search or filters.')
            ->assertDontSee('Canonical brands will appear here once they are created.');
    }

    public function test_resource_exposes_no_mutation_actions_or_legacy_routes(): void
    {
        $brand = CentralBrand::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ListCentralBrands::class)
            ->assertTableHeaderActionsExistInOrder([])
            ->assertTableActionsExistInOrder([])
            ->assertTableEmptyStateActionsExistInOrder([]);

        $this->actingAs($user);
        $this->assertFalse(CentralBrandResource::canCreate());
        $this->assertFalse(CentralBrandResource::canEdit($brand));
        $this->assertFalse(CentralBrandResource::canDelete($brand));
        $this->assertFalse(CentralBrandResource::canDeleteAny());

        $this->get('/admin/central/brands/create')
            ->assertNotFound();
        $this->get("/admin/central/brands/{$brand->getKey()}/edit")
            ->assertNotFound();
        $this->get('/admin/central/central-brands/create')
            ->assertNotFound();
        $this->get("/admin/central/central-brands/{$brand->getKey()}/edit")
            ->assertNotFound();
    }

    public function test_user_without_catalog_permission_cannot_open_ca_011(): void
    {
        $translator = User::factory()->create(['role' => UserRole::Translator]);

        $this->actingAs($translator)
            ->get(CentralBrandResource::getUrl('index'))
            ->assertForbidden();
    }
}
