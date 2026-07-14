<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\Builders\ProductProjectionBuilder;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\Site;
use App\Models\SiteOverride;
use App\Models\Translations\ProductTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductProjectionBuilderSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_deterministic_seo_with_override_translation_and_media_priority(): void
    {
        $locale = Locale::factory()->create(['code' => 'en', 'is_default' => true]);
        $site = Site::factory()->create(['domain' => 'example.test']);
        $product = CentralProduct::factory()->create([
            'name' => 'LG UltraGear 27GP850-B',
            'slug' => 'lg-ultragear-27gp850-b',
            'status' => CentralProductStatus::Active,
        ]);
        $ogAsset = MediaAsset::factory()->create(['original_path' => 'media/og-product.jpg']);

        ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'en',
            'name' => null,
            'seo_title' => 'Translated SEO title',
            'seo_description' => 'Translated SEO description',
        ]);
        SiteOverride::create([
            'site_id' => $site->id,
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'field' => 'meta_title',
            'locale_code' => 'en',
            'value_json' => ['value' => 'Local SEO title'],
            'status' => 'active',
        ]);
        MediaAssignment::factory()->for($ogAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => $product->id,
            'role' => 'og',
        ]);

        $projection = app(ProductProjectionBuilder::class)->build($site, $product, 'en');

        $this->assertSame('Local SEO title', $projection->seo['meta_title']);
        $this->assertSame('Translated SEO description', $projection->seo['meta_description']);
        $this->assertSame('LG UltraGear 27GP850-B', $projection->seo['h1']);
        $this->assertSame('https://example.test/products/lg-ultragear-27gp850-b', $projection->seo['canonical_url']);
        $this->assertSame('index,follow', $projection->seo['robots']);
        $this->assertSame('Local SEO title', $projection->seo['og_title']);
        $this->assertStringContainsString('media/og-product.jpg', $projection->seo['og_image']);
        $this->assertSame([], $projection->seo['hreflang']);
    }

    public function test_it_uses_title_fallback_and_noindex_for_a_non_active_projection(): void
    {
        $site = Site::factory()->create(['domain' => 'https://catalog.example']);
        $product = CentralProduct::factory()->create([
            'name' => 'Draft Product',
            'slug' => 'draft-product',
            'status' => CentralProductStatus::Draft,
        ]);

        $projection = app(ProductProjectionBuilder::class)->build($site, $product, 'en');

        $this->assertSame('Draft Product', $projection->seo['meta_title']);
        $this->assertSame('noindex,nofollow', $projection->seo['robots']);
        $this->assertSame('https://catalog.example/products/draft-product', $projection->seo['canonical_url']);
    }
}
