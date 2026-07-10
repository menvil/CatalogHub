<?php

namespace Tests\Feature\Services;

use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\MeasurementUnit;
use App\Services\Units\UnitFormatter;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitFormatterTest extends TestCase
{
    use RefreshDatabase;

    public function test_formats_values_with_symbols_and_precision(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $formatter = app(UnitFormatter::class);

        $this->assertSame('100 W', $formatter->format(100, 'watt'));
        $this->assertSame('1.5 l', $formatter->format(1.5, 'liter'));
        $this->assertSame('1.50 l', $formatter->format(1.5, 'liter', 2));
        $this->assertSame('1,5 l', $formatter->format(1.5, 'liter', null, 'bg_BG'));
    }

    public function test_unknown_unit_throws_explicit_exception(): void
    {
        $this->expectException(CannotConvertUnitException::class);

        app(UnitFormatter::class)->format(1, 'unknown_unit');
    }

    public function test_inactive_unit_code_cannot_be_formatted(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        MeasurementUnit::query()
            ->where('code', 'watt')
            ->update(['is_active' => false]);

        $this->expectException(CannotConvertUnitException::class);

        app(UnitFormatter::class)->format(100, 'watt');
    }
}
