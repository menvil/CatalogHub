<?php

namespace Tests\Feature\Smoke;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('smoke')]
class PublicSiteSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    private SiteCategoryProjection $category;

    private SiteProductProjection $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MultiCategorySiteSeeder::class);
        $this->site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $centralCategory = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        $centralProduct = CentralProduct::factory()->for($centralCategory, 'category')->create();
        $this->category = SiteCategoryProjection::query()->create([
            'site_id' => $this->site->id,
            'locale' => 'en-US',
            'central_category_id' => $centralCategory->id,
            'slug' => 'monitors',
            'title' => 'Monitors',
            'status' => ProjectionStatus::Active,
            'payload_json' => ['category' => ['description' => 'Projected monitors']],
        ]);
        $this->product = SiteProductProjection::query()->create([
            'site_id' => $this->site->id,
            'locale' => 'en-US',
            'central_product_id' => $centralProduct->id,
            'slug' => 'smoke-monitor',
            'title' => 'Smoke Monitor',
            'status' => ProjectionStatus::Active,
            'payload_json' => [
                'product' => ['description' => 'Projection smoke product.'],
                'brand' => ['name' => 'Acme'],
                'category' => ['id' => $centralCategory->id, 'label' => 'Monitors', 'slug' => 'monitors'],
                'spec_sections' => [],
            ],
            'media_json' => [],
            'built_at' => now(),
        ]);
        SiteSearchDocument::factory()->create([
            'site_id' => $this->site->id,
            'locale' => 'en-US',
            'document_id' => $centralProduct->id,
            'title' => $this->product->title,
            'slug' => $this->product->slug,
            'filter_values_json' => ['category_id' => $centralCategory->id],
            'payload_json' => $this->product->payload_json,
            'built_at' => $this->product->built_at,
        ]);
    }

    public function test_public_home_page_renders(): void
    {
        $this->get('http://tech-compare.test/en-US')
            ->assertOk()
            ->assertSee('Tech Compare Global')
            ->assertSee('Find the right technology');
    }

    public function test_public_listing_page_renders_from_projection_data(): void
    {
        $this->get("http://tech-compare.test/en-US/categories/{$this->category->slug}/products")
            ->assertOk()
            ->assertSee((string) $this->category->title);
    }

    public function test_public_product_page_renders_from_projection_data(): void
    {
        $this->get("http://tech-compare.test/en-US/products/{$this->product->slug}")
            ->assertOk()
            ->assertSee((string) $this->product->title);
    }
}
