<?php

namespace Tests\Feature\Services;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
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

    public function test_formats_text_boolean_enum_multi_enum_and_json_values(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create([
            'name' => 'General',
            'code' => 'general',
        ]);
        $product = CentralProduct::factory()->for($category, 'category')->create();

        $string = $this->createAttribute($category, $section, 'Model name', 'model_name', 'string');
        $text = $this->createAttribute($category, $section, 'Description', 'description', 'text');
        $boolean = $this->createAttribute($category, $section, 'USB-C', 'usb_c', 'boolean');
        $enum = $this->createAttribute($category, $section, 'Panel type', 'panel_type', 'enum');
        $multiEnum = $this->createAttribute($category, $section, 'Ports', 'ports', 'multi_enum');
        $json = $this->createAttribute($category, $section, 'Technical blob', 'technical_blob', 'json');

        AttributeOption::factory()->for($enum, 'attribute')->create(['code' => 'ips', 'label' => 'IPS']);
        AttributeOption::factory()->for($multiEnum, 'attribute')->create(['code' => 'hdmi', 'label' => 'HDMI']);
        AttributeOption::factory()->for($multiEnum, 'attribute')->create(['code' => 'usb_c', 'label' => 'USB-C']);

        $preview = app(GroupedSpecsPreviewBuilder::class)->build($product, [
            $string->id => ['value_text' => 'LG UltraGear'],
            $text->id => ['value_text' => 'Gaming monitor'],
            $boolean->id => ['value_bool' => false],
            $enum->id => ['value_enum_code' => 'ips'],
            $multiEnum->id => ['value_json' => ['hdmi', 'usb_c']],
            $json->id => ['value_json' => ['panel' => 'ips']],
        ]);

        $values = collect($preview[0]['attributes'])->pluck('value', 'code')->all();

        $this->assertSame('LG UltraGear', $values['model_name']);
        $this->assertSame('Gaming monitor', $values['description']);
        $this->assertSame('No', $values['usb_c']);
        $this->assertSame('IPS', $values['panel_type']);
        $this->assertSame('HDMI, USB-C', $values['ports']);
        $this->assertSame('{"panel":"ips"}', $values['technical_blob']);
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

    private function createAttribute(CentralCategory $category, AttributeSection $section, string $name, string $code, string $dataType): AttributeDefinition
    {
        return AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'name' => $name,
                'code' => $code,
                'data_type' => $dataType,
            ]);
    }
}
