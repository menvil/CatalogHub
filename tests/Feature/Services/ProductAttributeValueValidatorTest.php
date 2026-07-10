<?php

namespace Tests\Feature\Services;

use App\Exceptions\ProductAttributes\CannotSaveProductSpecsException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
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
