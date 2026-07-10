<?php

namespace Tests\Feature\Services;

use App\Services\Units\UnitParser;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitParserPowerAliasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_parses_watt_aliases_case_insensitively(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $parser = app(UnitParser::class);

        foreach (['100 W', '100W', '100 watt', '100 watts', '100 Вт', '100 ватт'] as $raw) {
            $this->assertSame('watt', $parser->parse($raw)->unit_code);
        }
    }
}
