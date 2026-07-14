<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
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

        $this->get('http://tech-compare.test/en-US/categories/monitors/products?sort=title&per_page=1')
            ->assertOk()
            ->assertSee('Alpha Display')
            ->assertDontSee('Zeta Display')
            ->assertDontSee('German Display')
            ->assertSee('page=2', false);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertFalse(collect($queries)->contains(fn (array $query): bool => str_contains($query['query'], 'central_products')));
    }

    private function productProjection(
        Site $site,
        CentralCategory $category,
        string $slug,
        string $title,
        string $locale = 'en-US',
    ): SiteProductProjection {
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id]);

        return SiteProductProjection::query()->create([
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
    }
}
