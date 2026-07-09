<?php

namespace Tests\Feature\Services;

use App\Services\Units\UnitConverter;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitConverterLengthTest extends TestCase
{
    use RefreshDatabase;

    public function test_converts_length_units(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $converter = app(UnitConverter::class);

        $this->assertEqualsWithDelta(1, $converter->convert(10, 'millimeter', 'centimeter'), 0.00001);
        $this->assertEqualsWithDelta(1, $converter->convert(2.54, 'centimeter', 'inch'), 0.00001);
        $this->assertEqualsWithDelta(1000, $converter->convert(1, 'meter', 'millimeter'), 0.00001);
        $this->assertEqualsWithDelta(12, $converter->convert(1, 'foot', 'inch'), 0.00001);
    }
}
