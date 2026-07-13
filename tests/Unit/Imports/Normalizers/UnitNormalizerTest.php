<?php

namespace Tests\Unit\Imports\Normalizers;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Services\Imports\Normalizers\UnitNormalizer;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UnitNormalizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MeasurementDimensionsSeeder::class,
            MetricMeasurementUnitsSeeder::class,
            ImperialMeasurementUnitsSeeder::class,
        ]);
    }

    #[DataProvider('measuredValues')]
    public function test_parses_and_converts_measured_values(
        string $rawValue,
        string $dimension,
        string $canonicalUnit,
        string $sourceUnit,
        float $expected,
    ): void {
        $definition = $this->definition($dimension, $canonicalUnit);

        $result = app(UnitNormalizer::class)->normalize($definition, $rawValue);

        $this->assertTrue($result->isValid);
        $this->assertEqualsWithDelta($expected, $result->value, 0.000001);
        $this->assertSame($sourceUnit, $result->metadata['source_unit']);
        $this->assertSame($canonicalUnit, $result->metadata['canonical_unit']);
        $this->assertSame($rawValue, $result->metadata['source_value']);
    }

    public function test_rejects_incompatible_unit_dimension(): void
    {
        $result = app(UnitNormalizer::class)->normalize(
            $this->definition('power', 'watt'),
            '27 inch'
        );

        $this->assertFalse($result->isValid);
        $this->assertSame('incompatible_unit_dimension', $result->errorCode);
    }

    public function test_integer_accepts_negligible_conversion_noise_but_rejects_real_fraction(): void
    {
        $definition = new AttributeDefinition([
            'name' => 'Temperature',
            'data_type' => AttributeDataType::Integer,
            'dimension' => 'temperature',
            'canonical_unit' => 'celsius',
        ]);

        $nearInteger = app(UnitNormalizer::class)->normalize($definition, '32 fahrenheit');
        $fraction = app(UnitNormalizer::class)->normalize($definition, '33 fahrenheit');

        $this->assertTrue($nearInteger->isValid);
        $this->assertSame(0.0, $nearInteger->value);
        $this->assertFalse($fraction->isValid);
        $this->assertSame('invalid_integer', $fraction->errorCode);
    }

    /** @return iterable<string, array{string, string, string, string, float}> */
    public static function measuredValues(): iterable
    {
        yield 'watt Cyrillic' => ['100 Вт', 'power', 'watt', 'watt', 100.0];
        yield 'inch' => ['27 inch', 'length', 'millimeter', 'inch', 685.8];
        yield 'liters' => ['5 liters', 'volume', 'liter', 'liter', 5.0];
        yield 'gallon' => ['1.3 gal', 'volume', 'liter', 'gallon_us', 4.9210353192];
        yield 'bar' => ['15 bar', 'pressure', 'bar', 'bar', 15.0];
    }

    private function definition(string $dimension, string $canonicalUnit): AttributeDefinition
    {
        return new AttributeDefinition([
            'name' => 'Measured value',
            'data_type' => AttributeDataType::Decimal,
            'dimension' => $dimension,
            'canonical_unit' => $canonicalUnit,
        ]);
    }
}
