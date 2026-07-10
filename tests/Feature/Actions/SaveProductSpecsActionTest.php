<?php

namespace Tests\Feature\Actions;

use App\Actions\ProductAttributes\SaveProductSpecsAction;
use App\Exceptions\ProductAttributes\CannotSaveProductSpecsException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveProductSpecsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_saves_product_specs_through_action(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $dimension = MeasurementDimension::factory()->create(['code' => 'frequency']);
        MeasurementUnit::factory()->for($dimension, 'dimension')->create([
            'code' => 'hertz',
            'symbol' => 'Hz',
            'factor_to_canonical' => '1',
            'is_canonical' => true,
        ]);
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'refresh_rate',
                'data_type' => 'decimal',
                'dimension' => 'frequency',
                'canonical_unit' => 'hertz',
            ]);

        app(SaveProductSpecsAction::class)->handle($product, [
            $attribute->id => [
                'raw_value' => '165 Hz',
                'value_number' => 165,
                'source_unit' => 'hertz',
                'confidence' => 1,
                'source_type' => 'manual',
            ],
        ]);

        $this->assertDatabaseHas('central_product_attribute_values', [
            'central_product_id' => $product->id,
            'attribute_definition_id' => $attribute->id,
            'value_type' => 'decimal',
            'raw_value' => '165 Hz',
            'canonical_unit' => 'hertz',
            'source_type' => 'manual',
        ]);
    }

    public function test_updates_existing_product_spec_value(): void
    {
        [$product, $attribute] = $this->productWithStringAttribute();
        CentralProductAttributeValue::factory()
            ->for($product, 'product')
            ->for($attribute, 'attributeDefinition')
            ->create([
                'value_type' => 'string',
                'value_text' => 'Old',
            ]);

        app(SaveProductSpecsAction::class)->handle($product, [
            $attribute->id => ['value_text' => 'New'],
        ]);

        $this->assertDatabaseHas('central_product_attribute_values', [
            'central_product_id' => $product->id,
            'attribute_definition_id' => $attribute->id,
            'value_text' => 'New',
        ]);
    }

    public function test_deletes_existing_product_spec_when_value_becomes_empty(): void
    {
        [$product, $attribute] = $this->productWithStringAttribute();
        CentralProductAttributeValue::factory()
            ->for($product, 'product')
            ->for($attribute, 'attributeDefinition')
            ->create([
                'value_type' => 'string',
                'value_text' => 'Old',
            ]);

        app(SaveProductSpecsAction::class)->handle($product, [
            $attribute->id => ['value_text' => ''],
        ]);

        $this->assertDatabaseMissing('central_product_attribute_values', [
            'central_product_id' => $product->id,
            'attribute_definition_id' => $attribute->id,
        ]);
    }

    public function test_invalid_payload_does_not_create_partial_rows(): void
    {
        [$product, $validAttribute, $invalidAttribute] = $this->productWithTwoAttributes();
        $exceptionWasThrown = false;

        try {
            app(SaveProductSpecsAction::class)->handle($product, [
                $validAttribute->id => ['value_text' => 'LG UltraGear'],
                $invalidAttribute->id => ['value_text' => 'fast'],
            ]);
        } catch (CannotSaveProductSpecsException) {
            $exceptionWasThrown = true;
        }

        $this->assertTrue($exceptionWasThrown);
        $this->assertDatabaseCount('central_product_attribute_values', 0);
    }

    public function test_saves_numeric_range_without_value_number(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'screen_size_range',
                'data_type' => 'decimal',
            ]);

        app(SaveProductSpecsAction::class)->handle($product, [
            $attribute->id => [
                'value_min' => 24,
                'value_max' => 32,
            ],
        ]);

        $this->assertDatabaseHas('central_product_attribute_values', [
            'central_product_id' => $product->id,
            'attribute_definition_id' => $attribute->id,
            'value_min' => 24,
            'value_max' => 32,
        ]);
    }

    /**
     * @return array{CentralProduct, AttributeDefinition}
     */
    private function productWithStringAttribute(): array
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'model_name',
                'data_type' => 'string',
            ]);

        return [$product, $attribute];
    }

    /**
     * @return array{CentralProduct, AttributeDefinition, AttributeDefinition}
     */
    private function productWithTwoAttributes(): array
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $validAttribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'model_name',
                'data_type' => 'string',
            ]);
        $invalidAttribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'refresh_rate',
                'data_type' => 'decimal',
            ]);

        return [$product, $validAttribute, $invalidAttribute];
    }
}
