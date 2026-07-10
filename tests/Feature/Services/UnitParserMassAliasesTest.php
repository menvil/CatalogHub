<?php

namespace Tests\Feature\Services;

use App\Services\Units\UnitParser;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitParserMassAliasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_parses_mass_aliases(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $parser = app(UnitParser::class);

        foreach (['2 kg', '2 кг', '2 kilogram'] as $raw) {
            $this->assertSame('kilogram', $parser->parse($raw)->unit_code);
        }

        foreach (['5 lb', '5 lbs', '5 pounds', '5 фунтов'] as $raw) {
            $this->assertSame('pound', $parser->parse($raw)->unit_code);
        }
    }
}
