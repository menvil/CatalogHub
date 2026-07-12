<?php

namespace Database\Factories;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CentralProductAttributeValue>
 */
class CentralProductAttributeValueFactory extends Factory
{
    protected $model = CentralProductAttributeValue::class;

    public function definition(): array
    {
        return [
            'central_product_id' => CentralProduct::factory(),
            'attribute_definition_id' => AttributeDefinition::factory(),
            'raw_value' => null,
            'value_type' => 'string',
            'value_text' => fake()->word(),
            'value_number' => null,
            'value_bool' => null,
            'value_enum_code' => null,
            'value_json' => null,
            'value_min' => null,
            'value_max' => null,
            'source_unit' => null,
            'canonical_value' => null,
            'canonical_unit' => null,
            'confidence' => null,
            'source_type' => null,
            'source_id' => null,
            'source_reference' => null,
        ];
    }
}
