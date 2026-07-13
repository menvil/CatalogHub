<?php

namespace Tests\Unit\Imports\Normalizers;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Services\Imports\Normalizers\BooleanNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BooleanNormalizerTest extends TestCase
{
    #[DataProvider('trueAliases')]
    public function test_parses_true_aliases(mixed $rawValue): void
    {
        $result = (new BooleanNormalizer)->normalize($this->definition(), $rawValue);

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->value);
    }

    #[DataProvider('falseAliases')]
    public function test_parses_false_aliases(mixed $rawValue): void
    {
        $result = (new BooleanNormalizer)->normalize($this->definition(), $rawValue);

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->value);
    }

    public function test_rejects_unknown_boolean_value(): void
    {
        $result = (new BooleanNormalizer)->normalize($this->definition(), 'sometimes');

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_boolean', $result->errorCode);
        $this->assertSame('sometimes', $result->rawValue);
    }

    /** @return iterable<string, array{mixed}> */
    public static function trueAliases(): iterable
    {
        yield 'boolean' => [true];
        yield 'integer' => [1];
        yield 'yes' => [' YES '];
        yield 'true' => ['true'];
        yield 'russian yes' => ['Да'];
        yield 'present' => ['есть'];
    }

    /** @return iterable<string, array{mixed}> */
    public static function falseAliases(): iterable
    {
        yield 'boolean' => [false];
        yield 'integer' => [0];
        yield 'no' => [' NO '];
        yield 'false' => ['false'];
        yield 'russian no' => ['Нет'];
        yield 'absent' => ['отсутствует'];
    }

    private function definition(): AttributeDefinition
    {
        return new AttributeDefinition([
            'name' => 'Available',
            'data_type' => AttributeDataType::Boolean,
        ]);
    }
}
