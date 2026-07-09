<?php

namespace Database\Factories;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttributeDefinition>
 */
class AttributeDefinitionFactory extends Factory
{
    protected $model = AttributeDefinition::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'central_category_id' => CentralCategory::factory(),
            'attribute_section_id' => AttributeSection::factory(),
            'code' => Str::snake($name),
            'name' => str($name)->headline()->toString(),
            'data_type' => 'string',
            'dimension' => null,
            'canonical_unit' => null,
            'position' => 0,
            'is_required' => false,
            'is_filterable' => false,
            'is_sortable' => false,
            'is_comparable' => false,
            'is_visible' => true,
            'is_searchable' => false,
        ];
    }
}
