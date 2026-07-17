<?php

namespace Tests\Feature\SiteAdmin;

use App\Filament\Resources\SiteResource;
use App\Filament\Resources\SiteResource\Pages\ProductsWithoutOffersReport;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\SiteSearchDocument;
use App\Models\User;
use App\Queries\Pricing\ProductsWithoutOffersQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsWithoutOffersReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_pagination_is_stable_when_product_names_are_tied(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $products = CentralProduct::factory()->count(3)->create(['name' => 'Tied Product']);

        foreach ($products as $product) {
            SiteProduct::query()->create([
                'site_id' => $site->id,
                'central_product_id' => $product->id,
                'visibility' => 'visible',
            ]);
        }

        $query = app(ProductsWithoutOffersQuery::class);
        $first = $query->paginate($site, perPage: 2, page: 1)->getCollection()->pluck('id')->all();
        $second = $query->paginate($site, perPage: 2, page: 2)->getCollection()->pluck('id')->all();

        $this->assertSame(
            SiteProduct::query()->orderBy('id')->pluck('id')->all(),
            [...$first, ...$second],
        );
        $this->assertSame([], array_values(array_intersect($first, $second)));
    }

    public function test_it_lists_visible_site_products_without_offers_and_excludes_hidden_or_covered_products(): void
    {
        [$site, $uncovered, $covered, $hidden] = $this->scenario();

        $results = app(ProductsWithoutOffersQuery::class)->forSite($site)->get();

        $this->assertEquals([$uncovered->id], $results->pluck('central_product_id')->all());
        $this->assertFalse($results->contains('central_product_id', $covered->id));
        $this->assertFalse($results->contains('central_product_id', $hidden->id));
    }

    public function test_report_filters_uncovered_products_by_category_and_brand(): void
    {
        [$site, $uncovered] = $this->scenario();
        $other = CentralProduct::factory()->create();
        SiteProduct::query()->create([
            'site_id' => $site->id,
            'central_product_id' => $other->id,
            'visibility' => 'visible',
        ]);

        $results = app(ProductsWithoutOffersQuery::class)->forSite(
            $site,
            categoryId: $uncovered->central_category_id,
            brandId: $uncovered->central_brand_id,
        )->get();

        $this->assertEquals([$uncovered->id], $results->pluck('central_product_id')->all());
    }

    public function test_site_admin_can_open_the_products_without_offers_report(): void
    {
        [$site, $uncovered, $covered, $hidden] = $this->scenario();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(ProductsWithoutOffersReport::getUrl(['record' => $site]))
            ->assertOk()
            ->assertSee('Products Without Offers')
            ->assertSee($uncovered->name)
            ->assertDontSee($covered->name)
            ->assertDontSee($hidden->name)
            ->assertSee('Price settings');

        $this->assertArrayHasKey('products-without-offers', SiteResource::getPages());
    }

    /** @return array{Site, CentralProduct, CentralProduct, CentralProduct} */
    private function scenario(): array
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $category = CentralCategory::factory()->create();
        $brand = CentralBrand::factory()->create();
        $uncovered = CentralProduct::factory()->create([
            'name' => 'Uncovered Product',
            'central_category_id' => $category->id,
            'central_brand_id' => $brand->id,
        ]);
        $covered = CentralProduct::factory()->create(['name' => 'Covered Product']);
        $hidden = CentralProduct::factory()->create(['name' => 'Hidden Product']);

        foreach ([[$uncovered, 'visible'], [$covered, 'visible'], [$hidden, 'hidden']] as [$product, $visibility]) {
            SiteProduct::query()->create([
                'site_id' => $site->id,
                'central_product_id' => $product->id,
                'visibility' => $visibility,
            ]);
        }
        SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'locale' => 'en',
            'document_id' => $covered->id,
            'offers_count' => 2,
        ]);
        SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'locale' => 'en',
            'document_id' => $uncovered->id,
            'offers_count' => 0,
        ]);

        return [$site, $uncovered, $covered, $hidden];
    }
}
