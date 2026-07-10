<?php

namespace Database\Factories;

use App\Models\AttributeDisplayRule;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttributeDisplayRule>
 */
class AttributeDisplayRuleFactory extends Factory
{
    protected $model = AttributeDisplayRule::class;

    public function definition(): array
    {
        return [
            'attribute_definition_id' => AttributeDefinition::factory(),
            'market_code' => AttributeDisplayRule::GLOBAL_MARKET_CODE,
            'locale' => AttributeDisplayRule::GLOBAL_LOCALE,
            'display_unit_id' => MeasurementUnit::factory(),
            'decimals' => null,
            'rounding_mode' => 'half_up',
            'suffix_style' => 'symbol',
        ];
    }
}
