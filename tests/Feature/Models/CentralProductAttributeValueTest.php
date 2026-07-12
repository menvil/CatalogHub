<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CentralProductAttributeValueTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_central_product_attribute_value_model(): void
    {
        $product = CentralProduct::factory()->create();
        $attribute = AttributeDefinition::factory()->create();

        $value = CentralProductAttributeValue::create([
            'central_product_id' => $product->id,
            'attribute_definition_id' => $attribute->id,
            'value_type' => 'decimal',
            'value_number' => 165,
            'canonical_value' => 165,
            'canonical_unit' => 'hertz',
            'value_bool' => true,
            'value_json' => ['ips'],
            'source_reference' => ['note' => 'Checked source'],
            'confidence' => 0.98,
        ]);

        $this->assertTrue($value->exists);
        $this->assertSame('165.000000', $value->value_number);
        $this->assertSame('165.000000', $value->canonical_value);
        $this->assertNull($value->value_bool);
        $this->assertNull($value->value_json);
        $this->assertSame(['note' => 'Checked source'], $value->source_reference);
        $this->assertSame('0.9800', $value->confidence);
    }

    public function test_rejects_confidence_outside_normalized_range(): void
    {
        $product = CentralProduct::factory()->create();
        $attribute = AttributeDefinition::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        CentralProductAttributeValue::create([
            'central_product_id' => $product->id,
            'attribute_definition_id' => $attribute->id,
            'value_type' => 'string',
            'value_text' => 'LG UltraGear',
            'confidence' => 1.5,
        ]);
    }
}
