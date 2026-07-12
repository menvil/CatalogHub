<?php

namespace Tests\Unit\Imports\Normalizers;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Services\Imports\Normalizers\NumberNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NumberNormalizerTest extends TestCase
{
    #[DataProvider('validNumbers')]
    public function test_parses_locale_style_numbers(mixed $rawValue, string $expected): void
    {
        $result = (new NumberNormalizer)->normalize($this->decimalDefinition(), $rawValue);

        $this->assertTrue($result->isValid);
        $this->assertSame($expected, $result->value);
    }

    public function test_rejects_invalid_text(): void
    {
        $result = (new NumberNormalizer)->normalize($this->decimalDefinition(), 'about twelve');

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_number', $result->errorCode);
    }

    public function test_integer_attribute_rejects_decimal_value(): void
    {
        $definition = new AttributeDefinition([
            'name' => 'Count',
            'data_type' => AttributeDataType::Integer,
        ]);

        $result = (new NumberNormalizer)->normalize($definition, '12.5');

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_integer', $result->errorCode);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function validNumbers(): iterable
    {
        yield 'integer' => [123, '123'];
        yield 'dot decimal' => ['123.45', '123.45'];
        yield 'comma decimal' => ['123,45', '123.45'];
        yield 'space thousands' => ['1 234,5', '1234.5'];
        yield 'non-breaking space' => ["1\u{00A0}234,50", '1234.5'];
        yield 'mixed European' => ['1.234,56', '1234.56'];
        yield 'mixed English' => ['1,234.56', '1234.56'];
        yield 'negative' => [' -0012,50 ', '-12.5'];
    }

    private function decimalDefinition(): AttributeDefinition
    {
        return new AttributeDefinition([
            'name' => 'Rating',
            'data_type' => AttributeDataType::Decimal,
        ]);
    }
}
