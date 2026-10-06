<?php

namespace Database\Factories;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttributeDefinition>
 */
class AttributeDefinitionFactory extends Factory
{
    protected $model = AttributeDefinition::class;

    public function global(): static
    {
        return $this;
    }

    /** Target fixture: local configuration belongs to an explicit assignment. */
    public function assignedTo(CentralCategory $category, array $configuration = []): static
    {
        $local = new \SplObjectStorage;

        return $this->afterMaking(function (AttributeDefinition $definition) use (&$local, $configuration): void {
            $fields = ['attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable'];
            $local[$definition] = [...array_intersect_key($definition->getAttributes(), array_flip($fields)), ...$configuration];
            foreach ($fields as $field) {
                unset($definition[$field]);
            }
        })->afterCreating(function (AttributeDefinition $definition) use ($category, &$local): void {
            $definition->assignments()->create(['central_category_id' => $category->id, ...$local[$definition]]);
        });
    }

    public function withCanonicalUnit(string $unitCode): static
    {
        return $this->state(function () use ($unitCode): array {
            $unit = MeasurementUnit::query()->where('code', $unitCode)->sole();

            return ['measurement_dimension_id' => $unit->dimension_id, 'canonical_measurement_unit_id' => $unit->id];
        });
    }

    public function measured(string $dimensionCode, string $unitCode): static
    {
        return $this->state(function () use ($dimensionCode, $unitCode): array {
            $dimension = MeasurementDimension::query()->where('code', $dimensionCode)->sole();
            $unit = MeasurementUnit::query()->where('code', $unitCode)->where('dimension_id', $dimension->id)->sole();

            return ['measurement_dimension_id' => $dimension->id, 'canonical_measurement_unit_id' => $unit->id];
        });
    }

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['code' => Str::snake($name), 'name' => str($name)->headline()->toString(), 'data_type' => 'string',
            'measurement_dimension_id' => null, 'canonical_measurement_unit_id' => null,
        ];
    }
}
