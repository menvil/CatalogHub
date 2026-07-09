<?php

namespace Tests\Feature\Units;

use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\MeasurementUnit;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementUnitConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MeasurementDimensionsSeeder::class);
        $this->seed(MetricMeasurementUnitsSeeder::class);
        $this->seed(ImperialMeasurementUnitsSeeder::class);
    }

    public function test_linear_units_convert_to_and_from_canonical(): void
    {
        $centimeter = MeasurementUnit::query()->where('code', 'centimeter')->firstOrFail();
        $kilogram = MeasurementUnit::query()->where('code', 'kilogram')->firstOrFail();
        $liter = MeasurementUnit::query()->where('code', 'liter')->firstOrFail();

        $this->assertSame(10.0, $centimeter->toCanonical(1));
        $this->assertSame(2.0, $centimeter->fromCanonical(20));
        $this->assertSame(1.0, $kilogram->toCanonical(1));
        $this->assertSame(1.0, $liter->toCanonical(1));
    }

    public function test_offset_units_convert_to_and_from_canonical(): void
    {
        $fahrenheit = MeasurementUnit::query()->where('code', 'fahrenheit')->firstOrFail();

        $this->assertEqualsWithDelta(0, $fahrenheit->toCanonical(32), 0.00001);
        $this->assertEqualsWithDelta(100, $fahrenheit->toCanonical(212), 0.00001);
        $this->assertEqualsWithDelta(32, $fahrenheit->fromCanonical(0), 0.00001);
    }

    public function test_from_canonical_rejects_zero_conversion_factor(): void
    {
        $unit = MeasurementUnit::query()->where('code', 'meter')->firstOrFail();
        $unit->forceFill(['factor_to_canonical' => '0']);

        $this->expectException(CannotConvertUnitException::class);

        $unit->fromCanonical(10);
    }
}
