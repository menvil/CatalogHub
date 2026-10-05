<?php

namespace Database\Factories;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CategoryAttributeAssignment> */
final class CategoryAttributeAssignmentFactory extends Factory
{
    protected $model = CategoryAttributeAssignment::class;

    public function definition(): array
    {
        return ['central_category_id' => CentralCategory::factory(), 'attribute_definition_id' => AttributeDefinition::factory()->global(), 'attribute_section_id' => null, 'position' => 0, 'is_required' => false, 'is_visible' => true, 'is_searchable' => false, 'is_sortable' => false];
    }
}
