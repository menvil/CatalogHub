<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\Builders\ProductProjectionBuilder;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductProjectionBuilderOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_locale_and_global_site_overrides_without_mutating_the_product(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create([
            'name' => 'Central Product Name',
            'slug' => 'central-product-name',
            'status' => CentralProductStatus::Active,
        ]);

        $this->override($site, $product, 'local_title', '', 'Global Product Name');
        $this->override($site, $product, 'local_title', 'en', 'Local Product Name');
        $this->override($site, $product, 'local_slug', '', 'local-product-name');
        $this->override($site, $product, 'intro_text', 'en', 'Local introduction');
        $this->override($site, $product, 'visibility', 'en', 'hidden');

        $projection = app(ProductProjectionBuilder::class)->build($site, $product, 'en');

        $this->assertSame('Local Product Name', $projection->title);
        $this->assertSame('local-product-name', $projection->slug);
        $this->assertSame('Local Product Name', $projection->payload['product']['title']);
        $this->assertSame('local-product-name', $projection->payload['product']['slug']);
        $this->assertSame('Local introduction', $projection->payload['product']['intro_text']);
        $this->assertSame('hidden', $projection->payload['product']['visibility']);
        $this->assertSame(ProjectionStatus::Pending, $projection->status);
        $this->assertSame('Central Product Name', $product->fresh()->name);
        $this->assertSame('central-product-name', $product->fresh()->slug);
    }

    private function override(
        Site $site,
        CentralProduct $product,
        string $field,
        string $locale,
        mixed $value,
    ): SiteOverride {
        return SiteOverride::create([
            'site_id' => $site->id,
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'field' => $field,
            'locale_code' => $locale,
            'value_json' => ['value' => $value],
            'status' => 'active',
        ]);
    }
}
