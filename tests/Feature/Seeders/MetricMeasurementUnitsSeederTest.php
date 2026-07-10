<?php

namespace Tests\Feature\Seeders;

use App\Models\MeasurementUnit;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricMeasurementUnitsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_metric_units_idempotently(): void
    {
        $this->seed(MeasurementDimensionsSeeder::class);
        $this->seed(MetricMeasurementUnitsSeeder::class);
        $this->seed(MetricMeasurementUnitsSeeder::class);

        foreach (['millimeter', 'centimeter', 'meter', 'gram', 'kilogram', 'milliliter', 'liter', 'watt', 'kilowatt', 'celsius', 'kelvin', 'bar', 'hertz', 'kilohertz'] as $code) {
            $this->assertDatabaseHas('measurement_units', ['code' => $code]);
        }

        $this->assertTrue(MeasurementUnit::query()->where('code', 'millimeter')->firstOrFail()->is_canonical);
        $this->assertTrue(MeasurementUnit::query()->where('code', 'kilogram')->firstOrFail()->is_canonical);
        $this->assertTrue(MeasurementUnit::query()->where('code', 'liter')->firstOrFail()->is_canonical);
    }
}
