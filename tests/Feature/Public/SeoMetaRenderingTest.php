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
use Tests\TestCase;

class SeoMetaRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_and_product_pages_render_projection_seo_meta(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $category = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id]);
        SiteCategoryProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_category_id' => $category->id,
            'slug' => 'monitors',
            'title' => 'Monitors',
            'status' => ProjectionStatus::Active,
            'payload_json' => ['category' => ['description' => 'Compare monitors']],
            'seo_json' => [
                'meta_title' => 'Monitor comparisons',
                'meta_description' => 'Compare projected monitor specifications.',
                'canonical_url' => 'https://tech-compare.test/en-US/categories/monitors',
                'og_title' => 'Monitor comparisons',
                'og_description' => 'Compare projected monitor specifications.',
                'og_image' => 'https://cdn.test/monitor-og.jpg',
                'robots' => 'index,follow',
            ],
        ]);
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $product->id,
            'slug' => 'aurora-27',
            'title' => 'Aurora 27',
            'status' => ProjectionStatus::Active,
            'payload_json' => ['category' => ['id' => $category->id, 'slug' => 'monitors', 'label' => 'Monitors']],
            'media_json' => [],
            'seo_json' => [
                'meta_title' => 'Aurora 27 specifications',
                'meta_description' => 'Full projected Aurora specifications.',
                'canonical_url' => 'https://tech-compare.test/en-US/products/aurora-27',
                'og_title' => 'Aurora 27 specifications',
                'og_description' => 'Full projected Aurora specifications.',
                'og_image' => 'https://cdn.test/aurora-og.jpg',
            ],
        ]);

        $this->get('http://tech-compare.test/en-US/categories/monitors')
            ->assertOk()
            ->assertSee('<title>Monitor comparisons</title>', false)
            ->assertSee('<meta name="description" content="Compare projected monitor specifications.">', false)
            ->assertSee('<link rel="canonical" href="https://tech-compare.test/en-US/categories/monitors">', false)
            ->assertSee('<meta property="og:image" content="https://cdn.test/monitor-og.jpg">', false);

        $this->get('http://tech-compare.test/en-US/products/aurora-27')
            ->assertOk()
            ->assertSee('<title>Aurora 27 specifications</title>', false)
            ->assertSee('<meta property="og:title" content="Aurora 27 specifications">', false)
            ->assertSee('<meta property="og:description" content="Full projected Aurora specifications.">', false)
            ->assertSee('<meta property="og:image" content="https://cdn.test/aurora-og.jpg">', false);
    }
}
