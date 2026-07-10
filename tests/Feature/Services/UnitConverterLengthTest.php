<?php

namespace Tests\Feature\Services;

use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\MeasurementUnit;
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

    public function test_model_instance_dimension_ids_are_compared_by_normalized_value(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $centimeter = MeasurementUnit::query()->where('code', 'centimeter')->firstOrFail();
        $inch = MeasurementUnit::query()->where('code', 'inch')->firstOrFail();
        $centimeter->forceFill(['dimension_id' => (string) $centimeter->dimension_id]);

        $this->assertEqualsWithDelta(1, app(UnitConverter::class)->convert(2.54, $centimeter, $inch), 0.00001);
    }

    public function test_inactive_unit_code_cannot_be_converted(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        MeasurementUnit::query()
            ->where('code', 'inch')
            ->update(['is_active' => false]);

        $this->expectException(CannotConvertUnitException::class);

        app(UnitConverter::class)->convert(1, 'inch', 'centimeter');
    }
}
