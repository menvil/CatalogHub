<?php

namespace Tests\Feature\Services;

use App\Services\Units\UnitParser;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitParserVolumeAliasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_parses_liter_and_gallon_aliases(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $parser = app(UnitParser::class);

        foreach (['5 l', '5 L', '5 liter', '5 litres', '5 литров'] as $raw) {
            $this->assertSame('liter', $parser->parse($raw)->unit_code);
        }

        foreach (['1.3 gal', '1,3 gal', '1.3 gallon'] as $raw) {
            $this->assertSame('gallon_us', $parser->parse($raw)->unit_code);
        }
    }
}
