<?php

namespace App\Services\ProductAttributes;

use App\Enums\AttributeDataType;
use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Services\Units\UnitConverter;
use App\Services\Units\UnitFormatter;

final class CanonicalValuePreviewer
{
    public function __construct(
        private readonly UnitConverter $unitConverter,
        private readonly UnitFormatter $unitFormatter,
    ) {}

    /**
     * @param  array<string, mixed>  $valueState
     * @return array{value: float|string, unit: string|null, label: string, warning: string|null}|null
     */
    public function preview(AttributeDefinition $attribute, array $valueState): ?array
    {
        if (! in_array($attribute->data_type, [AttributeDataType::Integer, AttributeDataType::Decimal], true)) {
            return null;
        }

        $value = $valueState['value_number'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        $sourceUnit = filled($valueState['source_unit'] ?? null) ? (string) $valueState['source_unit'] : null;
        $canonicalUnit = filled($attribute->canonical_unit) ? (string) $attribute->canonical_unit : $sourceUnit;

        if ($sourceUnit !== null && $canonicalUnit !== null) {
            try {
                $canonicalValue = $this->unitConverter->convert($value, $sourceUnit, $canonicalUnit);

                return [
                    'value' => $canonicalValue,
                    'unit' => $canonicalUnit,
                    'label' => $this->format($canonicalValue, $canonicalUnit),
                    'warning' => null,
                ];
            } catch (CannotConvertUnitException $exception) {
                return [
                    'value' => $value,
                    'unit' => $canonicalUnit,
                    'label' => (string) $value,
                    'warning' => $exception->getMessage(),
                ];
            }
        }

        if ($canonicalUnit !== null) {
            return [
                'value' => $value,
                'unit' => $canonicalUnit,
                'label' => $this->format($value, $canonicalUnit),
                'warning' => null,
            ];
        }

        return [
            'value' => $value,
            'unit' => null,
            'label' => (string) $value,
            'warning' => null,
        ];
    }

    private function format(float|string $value, string $unit): string
    {
        try {
            return $this->unitFormatter->format($value, $unit);
        } catch (CannotConvertUnitException) {
            return trim((string) $value.' '.$unit);
        }
    }
}
