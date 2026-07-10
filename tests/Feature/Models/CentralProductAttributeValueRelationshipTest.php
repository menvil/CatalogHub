<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralProductAttributeValueRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_product_has_attribute_values(): void
    {
        $product = CentralProduct::factory()->create();
        $value = CentralProductAttributeValue::factory()
            ->for($product, 'product')
            ->create();

        $this->assertCount(1, $product->attributeValues);
        $this->assertSame($value->id, $product->attributeValues->first()->id);
    }

    public function test_attribute_value_belongs_to_product(): void
    {
        $product = CentralProduct::factory()->create();
        $value = CentralProductAttributeValue::factory()
            ->for($product, 'product')
            ->create();

        $this->assertSame($product->id, $value->product->id);
    }

    public function test_attribute_value_belongs_to_attribute_definition(): void
    {
        $attribute = AttributeDefinition::factory()->create();
        $value = CentralProductAttributeValue::factory()
            ->for($attribute, 'attributeDefinition')
            ->create();

        $this->assertSame($attribute->id, $value->attributeDefinition->id);
    }

    public function test_attribute_definition_has_product_values(): void
    {
        $attribute = AttributeDefinition::factory()->create();
        $value = CentralProductAttributeValue::factory()
            ->for($attribute, 'attributeDefinition')
            ->create();

        $this->assertCount(1, $attribute->productValues);
        $this->assertSame($value->id, $attribute->productValues->first()->id);
    }
}
