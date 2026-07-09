<?php

namespace Tests\Feature\Services;

use App\Services\Units\UnitConverter;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitConverterVolumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_converts_volume_units(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $converter = app(UnitConverter::class);

        $this->assertEqualsWithDelta(1, $converter->convert(1000, 'milliliter', 'liter'), 0.00001);
        $this->assertEqualsWithDelta(1000, $converter->convert(1, 'liter', 'milliliter'), 0.00001);
        $this->assertEqualsWithDelta(3.785411784, $converter->convert(1, 'gallon_us', 'liter'), 0.000000001);
        $this->assertEqualsWithDelta(4.9210353192, $converter->toCanonical(1.3, 'gallon_us'), 0.000000001);
    }
}
