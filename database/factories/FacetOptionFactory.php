<?php

namespace Database\Factories;

use App\Models\FacetDefinition;
use App\Models\FacetOption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<FacetOption> */
class FacetOptionFactory extends Factory
{
    protected $model = FacetOption::class;

    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'facet_definition_id' => FacetDefinition::factory(),
            'value' => Str::slug($label, '_'),
            'label_override' => null,
            'position' => 0,
            'is_active' => true,
            'config_json' => null,
        ];
    }
}
