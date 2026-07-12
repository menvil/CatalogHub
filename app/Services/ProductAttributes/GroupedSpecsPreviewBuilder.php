<?php

namespace App\Services\ProductAttributes;

use App\Enums\AttributeDataType;
use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Services\Units\UnitConverter;
use App\Services\Units\UnitFormatter;

final class GroupedSpecsPreviewBuilder
{
    public function __construct(
        private readonly UnitConverter $unitConverter,
        private readonly UnitFormatter $unitFormatter,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $state
     * @return list<array{section: string, code: string, attributes: list<array{code: string, name: string, value: string}>}>
     */
    public function build(CentralProduct $product, array $state = []): array
    {
        $product->loadMissing([
            'category.attributeSections' => fn ($query) => $query->ordered(),
            'category.attributeSections.attributes' => fn ($query) => $query->ordered(),
            'category.attributeSections.attributes.options',
            'attributeValues',
        ]);

        if (! $product->category) {
            return [];
        }

        $preview = [];

        foreach ($product->category->attributeSections as $section) {
            $attributes = [];

            foreach ($section->attributes as $attribute) {
                $valueState = $state[$attribute->id] ?? $this->stateFromExistingValue(
                    $product->attributeValues->firstWhere('attribute_definition_id', $attribute->id),
                );
                $formattedValue = $this->formatValue($attribute, $valueState);

                if ($formattedValue === null) {
                    continue;
                }

                $attributes[] = [
                    'code' => $attribute->code,
                    'name' => $attribute->name,
                    'value' => $formattedValue,
                ];
            }

            $preview[] = [
                'section' => $section->name,
                'code' => $section->code,
                'attributes' => $attributes,
            ];
        }

        return $preview;
    }

    /**
     * @return array<string, mixed>
     */
    private function stateFromExistingValue(?CentralProductAttributeValue $value): array
    {
        if (! $value instanceof CentralProductAttributeValue) {
            return [];
        }

        return [
            'value_text' => $value->value_text,
            'value_number' => $value->value_number,
            'value_bool' => $value->value_bool,
            'value_enum_code' => $value->value_enum_code,
            'value_json' => $value->value_json,
            'canonical_value' => $value->canonical_value,
            'canonical_unit' => $value->canonical_unit,
            'source_unit' => $value->source_unit,
        ];
    }

    /**
     * @param  array<string, mixed>  $valueState
     */
    private function formatValue(AttributeDefinition $attribute, array $valueState): ?string
    {
        return match ($attribute->data_type) {
            AttributeDataType::Integer, AttributeDataType::Decimal => $this->formatNumeric($attribute, $valueState),
            AttributeDataType::String, AttributeDataType::Text => filled($valueState['value_text'] ?? null) ? (string) $valueState['value_text'] : null,
            AttributeDataType::Boolean => $this->formatBoolean($valueState),
            AttributeDataType::Enum => $this->formatEnum($attribute, $valueState),
            AttributeDataType::MultiEnum => $this->formatMultiEnum($attribute, $valueState),
            AttributeDataType::Json => ! empty($valueState['value_json']) ? json_encode($valueState['value_json'], JSON_THROW_ON_ERROR) : null,
        };
    }

    /**
     * @param  array<string, mixed>  $valueState
     */
    private function formatNumeric(AttributeDefinition $attribute, array $valueState): ?string
    {
        $value = $valueState['canonical_value'] ?? null;
        $unit = filled($valueState['canonical_unit'] ?? null)
            ? (string) $valueState['canonical_unit']
            : ($attribute->canonical_unit ?: null);

        if (blank($value) && filled($valueState['value_number'] ?? null)) {
            $value = $valueState['value_number'];

            if (filled($valueState['source_unit'] ?? null) && filled($unit)) {
                try {
                    $value = $this->unitConverter->convert($value, (string) $valueState['source_unit'], (string) $unit);
                } catch (CannotConvertUnitException) {
                    $unit = $valueState['source_unit'];
                }
            }
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (blank($unit)) {
            return (string) $value;
        }

        try {
            return $this->unitFormatter->format($value, (string) $unit);
        } catch (CannotConvertUnitException) {
            return trim((string) $value.' '.$unit);
        }
    }

    /**
     * @param  array<string, mixed>  $valueState
     */
    private function formatBoolean(array $valueState): ?string
    {
        if (! array_key_exists('value_bool', $valueState) || $valueState['value_bool'] === null || $valueState['value_bool'] === '') {
            return null;
        }

        $value = filter_var($valueState['value_bool'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $value === null ? null : ($value ? 'Yes' : 'No');
    }

    /**
     * @param  array<string, mixed>  $valueState
     */
    private function formatEnum(AttributeDefinition $attribute, array $valueState): ?string
    {
        if (blank($valueState['value_enum_code'] ?? null)) {
            return null;
        }

        $option = $attribute->options->firstWhere('code', $valueState['value_enum_code']);

        return $option === null ? (string) $valueState['value_enum_code'] : $option->label;
    }

    /**
     * @param  array<string, mixed>  $valueState
     */
    private function formatMultiEnum(AttributeDefinition $attribute, array $valueState): ?string
    {
        if (empty($valueState['value_json']) || ! is_array($valueState['value_json'])) {
            return null;
        }

        return collect($valueState['value_json'])
            ->map(function (string $code) use ($attribute): string {
                $option = $attribute->options->firstWhere('code', $code);

                return $option === null ? $code : $option->label;
            })
            ->implode(', ');
    }
}
