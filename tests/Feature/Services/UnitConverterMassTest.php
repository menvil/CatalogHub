<?php

namespace Tests\Feature\Services;

use App\Services\Units\UnitConverter;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitConverterMassTest extends TestCase
{
    use RefreshDatabase;

    public function test_converts_mass_units(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $converter = app(UnitConverter::class);

        $this->assertEqualsWithDelta(1, $converter->convert(1000, 'gram', 'kilogram'), 0.00001);
        $this->assertEqualsWithDelta(1000, $converter->convert(1, 'kilogram', 'gram'), 0.00001);
        $this->assertEqualsWithDelta(0.45359237, $converter->convert(1, 'pound', 'kilogram'), 0.00000001);
        $this->assertEqualsWithDelta(1, $converter->convert(16, 'ounce', 'pound'), 0.000001);
    }
}
