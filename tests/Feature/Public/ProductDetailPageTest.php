<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProductProjection;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_is_driven_by_the_localized_site_projection(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $category = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        $centralProduct = CentralProduct::factory()->create(['central_category_id' => $category->id]);
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $centralProduct->id,
            'slug' => 'aurora-27-pro',
            'title' => 'Aurora 27 Pro',
            'status' => ProjectionStatus::Active,
            'payload_json' => [
                'product' => ['description' => 'A projected 4K monitor.', 'model' => 'A27P'],
                'brand' => ['name' => 'Northstar'],
                'category' => ['id' => $category->id, 'label' => 'Monitors', 'slug' => 'monitors'],
                'spec_sections' => [['label' => 'Display', 'attributes' => [['label' => 'Resolution', 'display_value' => '3840 × 2160']]]],
            ],
            'media_json' => [],
            'seo_json' => ['title' => 'Aurora 27 Pro review'],
        ]);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('http://tech-compare.test/en-US/products/aurora-27-pro')
            ->assertOk()
            ->assertSee('Aurora 27 Pro')
            ->assertSee('Northstar')
            ->assertSee('Monitors')
            ->assertSee('A projected 4K monitor.');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertFalse(collect($queries)->contains(fn (array $query): bool => str_contains($query['query'], 'central_products')));
    }

    public function test_missing_product_projection_returns_not_found(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);

        $this->get('http://tech-compare.test/en-US/products/missing')->assertNotFound();
    }
}
