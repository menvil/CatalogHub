<?php

namespace Tests\Feature\Models;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeDataTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_supports_allowed_attribute_data_types(): void
    {
        foreach (AttributeDataType::cases() as $type) {
            $attribute = AttributeDefinition::factory()->create([
                'data_type' => $type->value,
            ]);

            $this->assertSame($type, $attribute->data_type);
        }
    }

    public function test_accepts_attribute_data_type_enum_values(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::Integer,
        ]);

        $this->assertSame(AttributeDataType::Integer, $attribute->data_type);
        $this->assertDatabaseHas('attribute_definitions', [
            'id' => $attribute->id,
            'data_type' => AttributeDataType::Integer->value,
        ]);
    }
}
