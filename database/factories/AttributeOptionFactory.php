<?php

namespace Database\Factories;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttributeOption>
 */
class AttributeOptionFactory extends Factory
{
    protected $model = AttributeOption::class;

    public function definition(): array
    {
        $label = fake()->unique()->word();

        return [
            'attribute_definition_id' => AttributeDefinition::factory(),
            'code' => Str::snake($label),
            'label' => str($label)->headline()->toString(),
            'position' => 0,
            'is_visible' => true,
        ];
    }
}
