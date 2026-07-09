<?php

namespace Database\Factories;

use App\Models\MeasurementDimension;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MeasurementDimension>
 */
class MeasurementDimensionFactory extends Factory
{
    protected $model = MeasurementDimension::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'code' => Str::snake($name),
            'name' => Str::headline($name),
            'description' => null,
            'base_unit_code' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
