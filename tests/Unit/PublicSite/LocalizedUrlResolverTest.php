<?php

namespace Tests\Unit\PublicSite;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LocalizedUrlResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_site_and_locale_aware_urls_from_projection_slugs(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $category = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id]);
        $categoryProjection = SiteCategoryProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_category_id' => $category->id,
            'slug' => 'best-monitors',
            'title' => 'Best Monitors',
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
        ]);
        $productProjection = SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $product->id,
            'slug' => 'aurora-27-pro',
            'title' => 'Aurora 27 Pro',
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
        ]);
        $urls = app(LocalizedUrlResolver::class);

        $this->assertSame('https://tech-compare.test/en-US', $urls->home($site, 'en-US'));
        $this->assertSame('https://tech-compare.test/en-US/categories/best-monitors', $urls->category($site, 'en-US', $categoryProjection));
        $this->assertSame('https://tech-compare.test/en-US/categories/best-monitors/products', $urls->listing($site, 'en-US', $categoryProjection));
        $this->assertSame('https://tech-compare.test/en-US/products/aurora-27-pro', $urls->product($site, 'en-US', $productProjection));
        $this->assertSame('https://tech-compare.test/en-US/search', $urls->search($site, 'en-US'));
        $this->assertSame('https://tech-compare.test/en-US/articles/demo-guide', $urls->article($site, 'en-US', 'demo-guide'));
    }

    public function test_it_uses_the_application_host_when_the_site_domain_is_empty(): void
    {
        config(['app.url' => 'https://fallback.catalog.test/base']);
        $site = new Site(['domain' => ' / ', 'settings_json' => []]);

        $this->assertSame(
            'https://fallback.catalog.test/en-US',
            app(LocalizedUrlResolver::class)->home($site, 'en-US'),
        );
    }

    public function test_it_rejects_url_generation_when_no_domain_can_be_resolved(): void
    {
        config(['app.url' => '']);
        $site = new Site(['domain' => '', 'settings_json' => []]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot generate a public URL without a domain.');

        app(LocalizedUrlResolver::class)->home($site, 'en-US');
    }
}
