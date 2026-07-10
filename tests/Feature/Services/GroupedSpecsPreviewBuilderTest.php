<?php

namespace Tests\Feature\Services;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use App\Services\ProductAttributes\GroupedSpecsPreviewBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupedSpecsPreviewBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_grouped_preview_with_existing_values(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create([
            'name' => 'Display',
            'code' => 'display',
        ]);
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'name' => 'Refresh rate',
                'code' => 'refresh_rate',
                'data_type' => 'decimal',
                'canonical_unit' => 'hertz',
            ]);

        CentralProductAttributeValue::factory()
            ->for($product, 'product')
            ->for($attribute, 'attributeDefinition')
            ->create([
                'value_type' => 'decimal',
                'canonical_value' => 165,
                'canonical_unit' => 'hertz',
            ]);

        $preview = app(GroupedSpecsPreviewBuilder::class)->build($product);

        $this->assertSame('Display', $preview[0]['section']);
        $this->assertSame('Refresh rate', $preview[0]['attributes'][0]['name']);
        $this->assertSame('165.000000 hertz', $preview[0]['attributes'][0]['value']);
    }

    public function test_returns_empty_preview_for_product_without_category(): void
    {
        $product = CentralProduct::factory()->create();

        $this->assertSame([], app(GroupedSpecsPreviewBuilder::class)->build($product));
    }

    public function test_converts_numeric_state_preview_to_canonical_unit(): void
    {
        $mass = MeasurementDimension::factory()->create(['code' => 'mass']);
        MeasurementUnit::factory()->for($mass, 'dimension')->create([
            'code' => 'kilogram',
            'symbol' => 'kg',
            'factor_to_canonical' => '1',
            'precision_default' => 3,
        ]);
        MeasurementUnit::factory()->for($mass, 'dimension')->create([
            'code' => 'pound',
            'symbol' => 'lb',
            'factor_to_canonical' => '0.45359237',
            'precision_default' => 3,
        ]);
        [$product, $attribute] = $this->productWithAttribute('Weight', 'weight', 'decimal', [
            'dimension' => 'mass',
            'canonical_unit' => 'kilogram',
        ]);

        $preview = app(GroupedSpecsPreviewBuilder::class)->build($product, [
            $attribute->id => [
                'value_number' => 2.2,
                'source_unit' => 'pound',
                'canonical_unit' => '',
            ],
        ]);

        $this->assertSame('0.998 kg', $preview[0]['attributes'][0]['value']);
    }

    public function test_omits_invalid_boolean_preview_state(): void
    {
        [$product, $attribute] = $this->productWithAttribute('USB-C', 'usb_c', 'boolean');

        $preview = app(GroupedSpecsPreviewBuilder::class)->build($product, [
            $attribute->id => ['value_bool' => 'not-bool'],
        ]);

        $this->assertSame([], $preview[0]['attributes']);
    }

    /**
     * @param  array<string, mixed>  $attributeOverrides
     * @return array{CentralProduct, AttributeDefinition}
     */
    private function productWithAttribute(string $name, string $code, string $dataType, array $attributeOverrides = []): array
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                ...$attributeOverrides,
                'name' => $name,
                'code' => $code,
                'data_type' => $dataType,
            ]);

        return [$product, $attribute];
    }
}
