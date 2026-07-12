<?php

namespace Tests\Unit\Imports\Normalizers;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Services\Imports\Normalizers\EnumNormalizer;
use App\Services\Imports\Normalizers\MultiEnumNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MultiEnumNormalizerTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('separatedValues')]
    public function test_parses_common_separators(string $rawValue): void
    {
        $definition = $this->definitionWithOptions();

        $result = $this->normalizer()->normalize($definition, $rawValue);

        $this->assertTrue($result->isValid);
        $this->assertSame(['wifi', 'bluetooth'], $result->value);
        $this->assertSame([], $result->metadata['unresolved_tokens']);
    }

    public function test_supports_array_input_and_deduplicates_options(): void
    {
        $definition = $this->definitionWithOptions();

        $result = $this->normalizer()->normalize($definition, ['Wi-Fi', 'Bluetooth', 'wifi']);

        $this->assertTrue($result->isValid);
        $this->assertSame(['wifi', 'bluetooth'], $result->value);
    }

    public function test_returns_known_options_and_reports_unknown_tokens(): void
    {
        $definition = $this->definitionWithOptions();

        $result = $this->normalizer()->normalize($definition, 'Wi-Fi, Zigbee');

        $this->assertFalse($result->isValid);
        $this->assertSame(['wifi'], $result->value);
        $this->assertSame(['Zigbee'], $result->metadata['unresolved_tokens']);
        $this->assertSame('unresolved_enum_options', $result->errorCode);
        $this->assertSame(3, AttributeOption::query()->count());
    }

    /** @return iterable<string, array{string}> */
    public static function separatedValues(): iterable
    {
        yield 'comma' => ['Wi-Fi, Bluetooth'];
        yield 'slash' => ['Wi-Fi / Bluetooth'];
        yield 'semicolon' => ['Wi-Fi; Bluetooth'];
        yield 'plus' => ['Wi-Fi + Bluetooth'];
    }

    private function normalizer(): MultiEnumNormalizer
    {
        return new MultiEnumNormalizer(new EnumNormalizer);
    }

    private function definitionWithOptions(): AttributeDefinition
    {
        $definition = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::MultiEnum,
        ]);

        foreach ([
            'wifi' => 'Wi-Fi',
            'bluetooth' => 'Bluetooth',
            'usb_c' => 'USB-C',
        ] as $code => $label) {
            AttributeOption::factory()->for($definition, 'attribute')->create(compact('code', 'label'));
        }

        return $definition;
    }
}
