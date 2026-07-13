<?php

namespace Tests\Unit\Imports\Normalizers;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\Translations\AttributeOptionTranslation;
use App\Services\Imports\Normalizers\EnumNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnumNormalizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_option_by_exact_code(): void
    {
        [$definition, $option] = $this->definitionAndOption();

        $result = (new EnumNormalizer)->normalize($definition, $option->code);

        $this->assertTrue($result->isValid);
        $this->assertSame($option->code, $result->value);
        $this->assertSame('code', $result->metadata['matched_by']);
    }

    public function test_resolves_label_and_localized_label_with_normalized_case_and_spacing(): void
    {
        [$definition, $option] = $this->definitionAndOption();
        AttributeOptionTranslation::factory()->for($option, 'attributeOption')->create([
            'label' => 'Тъмно Синьо',
        ]);
        $normalizer = new EnumNormalizer;

        $labelResult = $normalizer->normalize($definition, '  DARK---BLUE ');
        $localizedResult = $normalizer->normalize($definition, ' тъмно   синьо ');

        $this->assertSame($option->code, $labelResult->value);
        $this->assertSame('label', $labelResult->metadata['matched_by']);
        $this->assertSame($option->code, $localizedResult->value);
        $this->assertSame('localized_label', $localizedResult->metadata['matched_by']);
    }

    public function test_unknown_value_returns_error_without_creating_option(): void
    {
        [$definition] = $this->definitionAndOption();
        $count = AttributeOption::query()->count();

        $result = (new EnumNormalizer)->normalize($definition, 'Purple');

        $this->assertFalse($result->isValid);
        $this->assertSame('unknown_enum_option', $result->errorCode);
        $this->assertSame($count, AttributeOption::query()->count());
    }

    /** @return array{AttributeDefinition, AttributeOption} */
    private function definitionAndOption(): array
    {
        $definition = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::Enum,
        ]);
        $option = AttributeOption::factory()->for($definition, 'attribute')->create([
            'code' => 'navy',
            'label' => 'Dark Blue',
        ]);

        return [$definition, $option];
    }
}
