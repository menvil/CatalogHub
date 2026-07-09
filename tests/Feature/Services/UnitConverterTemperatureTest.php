<?php

namespace Tests\Feature\Services;

use App\Exceptions\Units\CannotConvertUnitException;
use App\Services\Units\UnitConverter;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitConverterTemperatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_converts_temperature_units_and_blocks_incompatible_dimensions(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $converter = app(UnitConverter::class);

        $this->assertEqualsWithDelta(32, $converter->convert(0, 'celsius', 'fahrenheit'), 0.00001);
        $this->assertEqualsWithDelta(212, $converter->convert(100, 'celsius', 'fahrenheit'), 0.00001);
        $this->assertEqualsWithDelta(0, $converter->convert(32, 'fahrenheit', 'celsius'), 0.00001);
        $this->assertEqualsWithDelta(0, $converter->convert(273.15, 'kelvin', 'celsius'), 0.00001);

        $this->expectException(CannotConvertUnitException::class);

        $converter->convert(1, 'celsius', 'meter');
    }
}
