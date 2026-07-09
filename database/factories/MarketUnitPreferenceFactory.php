<?php

namespace Database\Factories;

use App\Models\MarketUnitPreference;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketUnitPreference>
 */
class MarketUnitPreferenceFactory extends Factory
{
    protected $model = MarketUnitPreference::class;

    public function definition(): array
    {
        $dimension = MeasurementDimension::factory()->create();

        return [
            'market_code' => fake()->unique()->countryCode(),
            'dimension_id' => $dimension->id,
            'preferred_unit_id' => MeasurementUnit::factory()->for($dimension, 'dimension'),
        ];
    }
}
