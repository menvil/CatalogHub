<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\Builders\ProductProjectionBuilder;
use App\Models\AttributeDisplayRule;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\Market;
use App\Models\MeasurementUnit;
use App\Models\Site;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductProjectionBuilderUnitFormattingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_converts_and_formats_display_units_for_the_site_market_and_locale(): void
    {
        $this->seed([
            MeasurementDimensionsSeeder::class,
            MetricMeasurementUnitsSeeder::class,
            ImperialMeasurementUnitsSeeder::class,
        ]);

        $market = Market::factory()->create(['code' => 'DE']);
        $site = Site::factory()->for($market)->create();
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'diagonal',
                'data_type' => 'decimal',
                'dimension' => 'length',
                'canonical_unit' => 'centimeter',
            ]);
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $inch = MeasurementUnit::query()->where('code', 'inch')->firstOrFail();

        AttributeDisplayRule::create([
            'attribute_definition_id' => $attribute->id,
            'market_code' => 'DE',
            'locale' => 'de-DE',
            'display_unit_id' => $inch->id,
            'decimals' => 2,
            'rounding_mode' => 'half_up',
            'suffix_style' => 'symbol',
        ]);
        CentralProductAttributeValue::factory()->for($product, 'product')->for($attribute, 'attributeDefinition')->create([
            'value_type' => 'decimal',
            'value_number' => 3.81,
            'canonical_value' => 3.81,
            'canonical_unit' => 'centimeter',
        ]);

        $projection = app(ProductProjectionBuilder::class)->build($site, $product, 'de-DE');
        $attributePayload = $projection->payload['spec_sections'][0]['attributes'][0];

        $this->assertSame(3.81, $attributePayload['canonical_value']);
        $this->assertSame('centimeter', $attributePayload['canonical_unit']);
        $this->assertSame('1,50 "', $attributePayload['display_value']);
        $this->assertSame('inch', $attributePayload['display_unit']);
    }

    public function test_it_falls_back_to_a_raw_display_value_when_the_unit_is_unknown(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'data_type' => 'decimal',
                'canonical_unit' => 'parsec',
            ]);
        $product = CentralProduct::factory()->for($category, 'category')->create();

        CentralProductAttributeValue::factory()->for($product, 'product')->for($attribute, 'attributeDefinition')->create([
            'value_type' => 'decimal',
            'value_number' => 3,
            'canonical_value' => 3,
            'canonical_unit' => 'parsec',
        ]);

        $projection = app(ProductProjectionBuilder::class)->build($site, $product, 'en');
        $attributePayload = $projection->payload['spec_sections'][0]['attributes'][0];

        $this->assertSame('3 parsec', $attributePayload['display_value']);
        $this->assertSame('parsec', $attributePayload['display_unit']);
    }
}
