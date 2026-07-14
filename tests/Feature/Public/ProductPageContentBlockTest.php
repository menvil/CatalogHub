<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\ContentType;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ContentItem;
use App\Models\ContentRelation;
use App\Models\ContentTranslation;
use App\Models\Site;
use App\Models\SiteProductProjection;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductPageContentBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_prioritizes_direct_then_category_then_brand_content(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $category = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        $brand = CentralBrand::factory()->create();
        $product = CentralProduct::factory()->create([
            'central_category_id' => $category->id,
            'central_brand_id' => $brand->id,
        ]);
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $product->id,
            'slug' => 'content-product',
            'title' => 'Content product',
            'status' => ProjectionStatus::Active,
            'payload_json' => [
                'product' => ['description' => 'Projected product.'],
                'category' => ['id' => $category->id, 'label' => 'Monitors', 'slug' => 'monitors'],
                'brand' => ['id' => $brand->id, 'name' => $brand->name],
            ],
        ]);

        $direct = $this->content($site, 'Direct product guide', 'direct-product-guide');
        ContentRelation::factory()->for($direct)->product($product)->create();
        $categoryGuide = $this->content($site, 'Category guide', 'category-guide');
        ContentRelation::factory()->for($categoryGuide)->category($category)->create();
        $brandGuide = $this->content($site, 'Brand guide', 'brand-guide');
        ContentRelation::factory()->for($brandGuide)->brand($brand)->create();
        $draft = ContentItem::factory()->draft()->for($site)->create();
        ContentTranslation::factory()->published()->for($draft)->create([
            'locale' => 'en-US',
            'title' => 'Draft related guide',
            'slug' => 'draft-related-guide',
        ]);
        ContentRelation::factory()->for($draft)->product($product)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->get('http://tech-compare.test/en-US/products/content-product');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response
            ->assertOk()
            ->assertSee('Related guides and articles')
            ->assertSeeInOrder(['Direct product guide', 'Category guide', 'Brand guide'])
            ->assertDontSee('Draft related guide');
        $this->assertFalse(collect($queries)->contains(
            fn (array $query): bool => str_contains($query['query'], 'central_products'),
        ));
    }

    public function test_product_page_omits_related_content_section_when_empty(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $product = CentralProduct::factory()->create();
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $product->id,
            'slug' => 'without-content',
            'title' => 'Without content',
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
        ]);

        $this->get('http://tech-compare.test/en-US/products/without-content')
            ->assertOk()
            ->assertDontSee('Related guides and articles');
    }

    private function content(Site $site, string $title, string $slug): ContentItem
    {
        $item = ContentItem::factory()->published()->for($site)->create([
            'type' => ContentType::BuyingGuide,
        ]);
        ContentTranslation::factory()->published()->for($item)->create([
            'locale' => 'en-US',
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $title.' excerpt.',
        ]);

        return $item;
    }
}
