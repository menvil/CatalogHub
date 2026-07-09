<?php

namespace Tests\Feature\Services;

use App\Exceptions\Units\CannotParseUnitException;
use App\Services\Units\UnitParser;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitParserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);
    }

    public function test_parses_basic_values(): void
    {
        $parser = app(UnitParser::class);

        $this->assertSame('watt', $parser->parse('100 W')->unit_code);
        $this->assertSame('liter', $parser->parse('5 l')->unit_code);
        $this->assertSame('inch', $parser->parse('27 inch')->unit_code);
        $this->assertSame('gallon_us', $parser->parse('1.3 gal')->unit_code);

        $parsed = $parser->parse('2,5 kg');

        $this->assertSame(2.5, $parsed->value);
        $this->assertSame('kilogram', $parsed->unit_code);
        $this->assertSame('2,5', $parsed->raw_value);
        $this->assertSame('kg', $parsed->raw_unit);
    }

    public function test_unknown_unit_throws_explicit_exception(): void
    {
        $this->expectException(CannotParseUnitException::class);

        app(UnitParser::class)->parse('10 parsecs');
    }
}
