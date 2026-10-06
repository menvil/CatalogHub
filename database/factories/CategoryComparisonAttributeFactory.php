<?php

namespace Database\Factories;

use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CategoryComparisonAttribute> */
final class CategoryComparisonAttributeFactory extends Factory
{
    protected $model = CategoryComparisonAttribute::class;

    public function definition(): array
    {
        return [
            'category_attribute_assignment_id' => CategoryAttributeAssignment::factory(),
            'central_category_id' => fn (array $attributes): int => CategoryAttributeAssignment::query()->findOrFail($attributes['category_attribute_assignment_id'])->central_category_id,
            'position' => 0,
            'is_visible' => true,
        ];
    }
}
