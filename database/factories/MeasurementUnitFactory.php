<?php

namespace Database\Factories;

use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MeasurementUnit>
 */
class MeasurementUnitFactory extends Factory
{
    protected $model = MeasurementUnit::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'dimension_id' => MeasurementDimension::factory(),
            'code' => Str::snake($name),
            'symbol' => Str::lower(Str::substr($name, 0, 3)),
            'name' => Str::headline($name),
            'system' => 'metric',
            'factor_to_canonical' => '1',
            'offset_to_canonical' => '0',
            'precision_default' => 2,
            'aliases_json' => [],
            'is_canonical' => false,
            'is_active' => true,
        ];
    }
}
