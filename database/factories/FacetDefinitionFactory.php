<?php

namespace Database\Factories;

use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<FacetDefinition> */
class FacetDefinitionFactory extends Factory
{
    protected $model = FacetDefinition::class;

    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'category_id' => CentralCategory::factory(),
            'attribute_definition_id' => null,
            'code' => Str::snake($label),
            'label_override' => null,
            'facet_type' => FacetType::Checkbox,
            'source_type' => FacetSourceType::Brand,
            'is_active' => true,
            'is_filterable' => true,
            'is_visible' => true,
            'is_collapsible' => true,
            'default_collapsed' => false,
            'position' => 0,
            'config_json' => null,
        ];
    }
}
