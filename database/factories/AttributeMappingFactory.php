<?php

namespace Database\Factories;

use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\ImportSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttributeMapping> */
final class AttributeMappingFactory extends Factory
{
    protected $model = AttributeMapping::class;

    public function definition(): array
    {
        $key = fake()->unique()->word();

        return [
            'import_source_id' => ImportSource::factory(),
            'category_attribute_assignment_id' => CategoryAttributeAssignment::factory(),
            'category_id' => fn (array $attributes): int => CategoryAttributeAssignment::query()->findOrFail($attributes['category_attribute_assignment_id'])->central_category_id,
            'raw_key' => $key,
            'normalized_raw_key' => $key,
            'status' => 'reviewed',
            'confidence' => 1,
            'mapping_type' => 'attribute',
            'notes' => null,
        ];
    }

    public function unmapped(): static
    {
        return $this->state(['category_attribute_assignment_id' => null, 'category_id' => CentralCategory::factory(), 'status' => 'auto', 'confidence' => 0]);
    }
}
