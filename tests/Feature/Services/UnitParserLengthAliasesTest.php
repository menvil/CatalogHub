<?php

namespace Tests\Feature\Services;

use App\Services\Units\UnitParser;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitParserLengthAliasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_parses_length_aliases(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $parser = app(UnitParser::class);

        foreach (['27 inch', '27 inches', '27"', '27 in'] as $raw) {
            $this->assertSame('inch', $parser->parse($raw)->unit_code);
        }

        foreach (['100 mm', '100 мм'] as $raw) {
            $this->assertSame('millimeter', $parser->parse($raw)->unit_code);
        }

        foreach (['10 cm', '10 см'] as $raw) {
            $this->assertSame('centimeter', $parser->parse($raw)->unit_code);
        }
    }
}
