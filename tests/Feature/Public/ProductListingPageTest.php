<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductListingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_paginates_current_site_and_locale_product_projections(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $category = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        SiteCategoryProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_category_id' => $category->id,
            'slug' => 'monitors',
            'title' => 'Monitors',
            'status' => ProjectionStatus::Active,
            'payload_json' => ['category' => ['description' => 'Projected monitors']],
        ]);
        $this->productProjection($site, $category, 'zeta-display', 'Zeta Display');
        $this->productProjection($site, $category, 'alpha-display', 'Alpha Display');
        $this->productProjection($site, $category, 'de-display', 'German Display', 'de-DE');
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('http://tech-compare.test/en-US/categories/monitors/products?sort=name_asc&price_from=50&per_page=1')
            ->assertOk()
            ->assertSee('Alpha Display')
            ->assertDontSee('Zeta Display')
            ->assertDontSee('German Display')
            ->assertSee('page=2', false)
            ->assertSee('price_from=50', false);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertFalse(collect($queries)->contains(fn (array $query): bool => str_contains($query['query'], 'central_products')));
        $this->assertTrue(collect($queries)->contains(fn (array $query): bool => str_contains($query['query'], 'site_search_documents')));
    }

    public function test_filtered_listing_renders_facet_ux_and_safe_seo_metadata(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $category = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        SiteCategoryProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_category_id' => $category->id,
            'slug' => 'monitors',
            'title' => 'Monitors',
            'status' => ProjectionStatus::Active,
            'payload_json' => ['category' => ['description' => 'Projected monitors']],
        ]);
        $this->productProjection($site, $category, 'lg-display', 'LG Display', filterValues: [
            'brand_slug' => 'lg',
        ]);
        $this->productProjection($site, $category, 'samsung-display', 'Samsung Display', filterValues: [
            'brand_slug' => 'samsung',
        ]);

        $this->get('http://tech-compare.test/en-US/categories/monitors/products?brand=lg')
            ->assertOk()
            ->assertSee('LG Display')
            ->assertDontSee('Samsung Display')
            ->assertSee('data-desktop-filter-sidebar', false)
            ->assertSee('data-mobile-filter-drawer', false)
            ->assertSee('data-active-filters', false)
            ->assertSee('LG')
            ->assertSee('<meta name="robots" content="noindex,follow">', false)
            ->assertSee('<link rel="canonical" href="https://tech-compare.test/en-US/categories/monitors/products">', false);
    }

    public function test_search_document_without_projection_or_slug_uses_safe_link_fallback(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $category = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        SiteCategoryProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_category_id' => $category->id,
            'slug' => 'monitors',
            'title' => 'Monitors',
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
        ]);
        SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'title' => 'Document without slug',
            'slug' => null,
            'filter_values_json' => ['category_id' => $category->id],
        ]);

        $response = $this->get('http://tech-compare.test/en-US/categories/monitors/products');

        $response->assertOk()->assertSee('Document without slug');
        $this->assertMatchesRegularExpression('/data-product-card.*?href="#"/s', $response->getContent());
    }

    private function productProjection(
        Site $site,
        CentralCategory $category,
        string $slug,
        string $title,
        string $locale = 'en-US',
        array $filterValues = [],
    ): SiteProductProjection {
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id]);

        $projection = SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => $locale,
            'central_product_id' => $product->id,
            'slug' => $slug,
            'title' => $title,
            'status' => ProjectionStatus::Active,
            'payload_json' => ['category' => ['id' => $category->id, 'slug' => $category->slug]],
            'media_json' => [],
            'search_summary_json' => ['key_specs' => ['27 inch', '4K']],
            'built_at' => now(),
        ]);

        SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'locale' => $locale,
            'document_id' => $product->id,
            'title' => $title,
            'slug' => $slug,
            'filter_values_json' => ['category_id' => $category->id, ...$filterValues],
            'sort_values_json' => ['title' => $title, 'rating' => null],
            'min_price' => '100.00',
            'payload_json' => $projection->payload_json,
            'built_at' => $projection->built_at,
        ]);

        return $projection;
    }
}
