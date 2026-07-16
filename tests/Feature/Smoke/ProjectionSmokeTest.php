<?php

namespace Tests\Feature\Smoke;

use App\Domains\Projections\SiteSyncService;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\Site;
use App\Models\SiteProductProjection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('smoke')]
class ProjectionSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_product_builds_a_localized_persisted_projection(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $brand = CentralBrand::factory()->create(['name' => 'Acme']);
        $category = CentralCategory::factory()->create(['name' => 'Monitors']);
        $section = AttributeSection::factory()->for($category, 'category')->create([
            'code' => 'display',
            'name' => 'Display',
        ]);
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'refresh_rate',
                'name' => 'Refresh rate',
                'data_type' => 'integer',
                'canonical_unit' => 'hertz',
            ]);
        $product = CentralProduct::factory()
            ->for($brand, 'brand')
            ->for($category, 'category')
            ->create([
                'name' => 'Acme Smoke Monitor',
                'slug' => 'acme-smoke-monitor',
                'status' => CentralProductStatus::Active,
                'version' => 3,
            ]);
        CentralProductAttributeValue::factory()
            ->for($product, 'product')
            ->for($attribute, 'attributeDefinition')
            ->create([
                'value_type' => 'integer',
                'value_number' => 165,
                'canonical_value' => 165,
                'canonical_unit' => 'hertz',
            ]);

        $projection = app(SiteSyncService::class)->syncProduct($site, $product, 'en');
        $projection->refresh();

        $this->assertInstanceOf(SiteProductProjection::class, $projection);
        $this->assertSame($site->id, $projection->site_id);
        $this->assertSame($product->id, $projection->central_product_id);
        $this->assertSame('en', $projection->locale);
        $this->assertSame('Acme Smoke Monitor', $projection->title);
        $this->assertSame('display', $projection->payload_json['spec_sections'][0]['code']);
        $this->assertSame('refresh_rate', $projection->payload_json['spec_sections'][0]['attributes'][0]['code']);
        $this->assertSame('165 hertz', $projection->payload_json['spec_sections'][0]['attributes'][0]['display_value']);
        $this->assertSame('active', $projection->status->value);
        $this->assertSame(3, $projection->central_product_version);
        $this->assertDatabaseHas('site_search_documents', [
            'site_id' => $site->id,
            'locale' => 'en',
            'document_type' => 'product',
            'document_id' => $product->id,
        ]);
    }
}
