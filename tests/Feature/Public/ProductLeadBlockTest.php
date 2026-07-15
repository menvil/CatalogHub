<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\SiteStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\SiteProductProjection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnablesSiteLocales;
use Tests\TestCase;

class ProductLeadBlockTest extends TestCase
{
    use EnablesSiteLocales;
    use RefreshDatabase;

    public function test_product_page_shows_lead_block_when_feature_is_enabled(): void
    {
        $this->productPageContext(featureEnabled: true);

        $this->get('http://leads.test/en-US/products/test-product')
            ->assertOk()
            ->assertSee('Need help choosing?')
            ->assertSee('Request help');
    }

    public function test_product_page_hides_lead_block_when_feature_is_disabled(): void
    {
        $this->productPageContext(featureEnabled: false);

        $this->get('http://leads.test/en-US/products/test-product')
            ->assertOk()
            ->assertDontSee('Need help choosing?')
            ->assertDontSee('Request help');
    }

    private function productPageContext(bool $featureEnabled): void
    {
        $site = Site::factory()->create([
            'domain' => 'leads.test',
            'default_locale' => 'en-US',
            'status' => SiteStatus::Active,
        ]);
        $this->enableLocale($site, 'en-US');
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'leads',
            'is_enabled' => $featureEnabled,
        ]);
        $product = CentralProduct::factory()->create();
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $product->id,
            'slug' => 'test-product',
            'title' => 'Test product',
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
        ]);
    }
}
