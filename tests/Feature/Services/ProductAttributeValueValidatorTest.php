<?php

namespace Tests\Feature\Services;

use App\Exceptions\ProductAttributes\CannotSaveProductSpecsException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use App\Services\ProductAttributes\ProductAttributeValueValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAttributeValueValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_text_value_for_numeric_attribute(): void
    {
        $product = $this->productWithAttribute('refresh_rate', 'decimal');

        $this->expectException(CannotSaveProductSpecsException::class);

        app(ProductAttributeValueValidator::class)->validate($product, [
            'refresh_rate' => ['value_text' => 'fast'],
        ]);
    }

    public function test_accepts_text_value_for_string_attribute(): void
    {
        $product = $this->productWithAttribute('model_name', 'string');

        $validated = app(ProductAttributeValueValidator::class)->validate($product, [
            'model_name' => ['value_text' => 'LG UltraGear'],
        ]);

        $this->assertSame('LG UltraGear', array_values($validated)[0]['value_text']);
    }

    public function test_rejects_non_boolean_value_for_boolean_attribute(): void
    {
        $product = $this->productWithAttribute('has_usb_c', 'boolean');

        $this->expectException(CannotSaveProductSpecsException::class);

        app(ProductAttributeValueValidator::class)->validate($product, [
            'has_usb_c' => ['value_bool' => 'yes'],
        ]);
    }

    public function test_rejects_unknown_enum_option(): void
    {
        [$product] = $this->productWithEnumAttribute('panel_type', 'enum', ['ips']);

        $this->expectException(CannotSaveProductSpecsException::class);

        app(ProductAttributeValueValidator::class)->validate($product, [
            'panel_type' => ['value_enum_code' => 'unknown'],
        ]);
    }

    public function test_rejects_unknown_multi_enum_options(): void
    {
        [$product] = $this->productWithEnumAttribute('ports', 'multi_enum', ['hdmi']);

        $this->expectException(CannotSaveProductSpecsException::class);

        app(ProductAttributeValueValidator::class)->validate($product, [
            'ports' => ['value_json' => ['hdmi', 'usb_c']],
        ]);
    }

    public function test_rejects_source_unit_from_different_dimension(): void
    {
        $product = $this->productWithNumericAttribute('weight', 'mass', 'kilogram');
        $this->createUnit('mass', 'kilogram', 'kg');
        $this->createUnit('volume', 'liter', 'l');

        $this->expectException(CannotSaveProductSpecsException::class);

        app(ProductAttributeValueValidator::class)->validate($product, [
            'weight' => [
                'value_number' => 10,
                'source_unit' => 'liter',
            ],
        ]);
    }

    public function test_allows_source_unit_from_same_dimension(): void
    {
        $product = $this->productWithNumericAttribute('weight', 'mass', 'kilogram');
        $this->createUnit('mass', 'kilogram', 'kg');
        $this->createUnit('mass', 'pound', 'lb', '0.45359237');

        $validated = app(ProductAttributeValueValidator::class)->validate($product, [
            'weight' => [
                'value_number' => 2.2,
                'source_unit' => 'pound',
            ],
        ]);

        $value = array_values($validated)[0];

        $this->assertSame('pound', $value['source_unit']);
        $this->assertSame('kilogram', $value['canonical_unit']);
    }

    public function test_allows_confidence_between_zero_and_one(): void
    {
        $product = $this->productWithAttribute('refresh_rate', 'decimal');

        $validated = app(ProductAttributeValueValidator::class)->validate($product, [
            'refresh_rate' => [
                'value_number' => 165,
                'confidence' => 0.95,
            ],
        ]);

        $this->assertSame(0.95, array_values($validated)[0]['confidence']);
    }

    public function test_rejects_confidence_greater_than_one(): void
    {
        $product = $this->productWithAttribute('refresh_rate', 'decimal');

        $this->expectException(CannotSaveProductSpecsException::class);

        app(ProductAttributeValueValidator::class)->validate($product, [
            'refresh_rate' => [
                'value_number' => 165,
                'confidence' => 1.5,
            ],
        ]);
    }

    public function test_accepts_source_reference_metadata_for_attribute_value(): void
    {
        $product = $this->productWithAttribute('refresh_rate', 'decimal');

        $validated = app(ProductAttributeValueValidator::class)->validate($product, [
            'refresh_rate' => [
                'value_number' => 165,
                'source_type' => 'manual',
                'source_reference' => ['note' => 'Checked manufacturer website'],
            ],
        ]);

        $value = array_values($validated)[0];

        $this->assertSame('manual', $value['source_type']);
        $this->assertSame(['note' => 'Checked manufacturer website'], $value['source_reference']);
    }

    private function productWithAttribute(string $code, string $dataType): CentralProduct
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();

        AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => $code,
                'data_type' => $dataType,
            ]);

        return $product;
    }

    private function productWithNumericAttribute(string $code, string $dimension, string $canonicalUnit): CentralProduct
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();

        AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => $code,
                'data_type' => 'decimal',
                'dimension' => $dimension,
                'canonical_unit' => $canonicalUnit,
            ]);

        return $product;
    }

    private function createUnit(string $dimensionCode, string $unitCode, string $symbol, string $factor = '1'): void
    {
        $dimension = MeasurementDimension::query()->firstOrCreate(
            ['code' => $dimensionCode],
            [
                'name' => str($dimensionCode)->headline()->toString(),
                'base_unit_code' => null,
                'sort_order' => 0,
                'is_active' => true,
            ],
        );

        MeasurementUnit::factory()->for($dimension, 'dimension')->create([
            'code' => $unitCode,
            'symbol' => $symbol,
            'factor_to_canonical' => $factor,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{CentralProduct, AttributeDefinition}
     */
    private function productWithEnumAttribute(string $code, string $dataType, array $options): array
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => $code,
                'data_type' => $dataType,
            ]);

        foreach ($options as $position => $optionCode) {
            AttributeOption::factory()->for($attribute, 'attribute')->create([
                'code' => $optionCode,
                'label' => $optionCode,
                'position' => $position,
            ]);
        }

        return [$product, $attribute];
    }
}
