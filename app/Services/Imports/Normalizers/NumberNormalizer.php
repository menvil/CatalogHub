<?php

namespace App\Services\Imports\Normalizers;

use App\Contracts\Imports\AttributeValueNormalizerInterface;
use App\Data\Imports\NormalizedAttributeValueData;
use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;

final class NumberNormalizer implements AttributeValueNormalizerInterface
{
    public function supports(AttributeDefinition $definition): bool
    {
        return in_array($definition->data_type, [AttributeDataType::Integer, AttributeDataType::Decimal], true)
            && blank($definition->dimension)
            && blank($definition->canonical_unit);
    }

    public function normalize(
        AttributeDefinition $definition,
        mixed $rawValue,
    ): NormalizedAttributeValueData {
        $normalized = $this->normalizeNumber($rawValue);

        if ($normalized === null) {
            return NormalizedAttributeValueData::failure(
                $rawValue,
                'invalid_number',
                'The raw value is not a valid locale-style number.',
            );
        }

        if ($definition->data_type === AttributeDataType::Integer && str_contains($normalized, '.')) {
            return NormalizedAttributeValueData::failure(
                $rawValue,
                'invalid_integer',
                'The raw value contains a decimal fraction for an integer attribute.',
            );
        }

        return NormalizedAttributeValueData::success($normalized, $rawValue);
    }

    private function normalizeNumber(mixed $rawValue): ?string
    {
        if (is_int($rawValue)) {
            return (string) $rawValue;
        }

        if (is_float($rawValue)) {
            if (! is_finite($rawValue)) {
                return null;
            }

            $rawValue = $this->expandScientificNotation((string) $rawValue);
        }

        if (! is_string($rawValue)) {
            return null;
        }

        $value = preg_replace('/[\s\x{00A0}\x{202F}\']/u', '', trim($rawValue)) ?? '';

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $commaIsDecimal = strrpos($value, ',') > strrpos($value, '.');
            $value = $commaIsDecimal
                ? str_replace(['.', ','], ['', '.'], $value)
                : str_replace(',', '', $value);
        } else {
            $separator = str_contains($value, ',') ? ',' : (str_contains($value, '.') ? '.' : null);

            if ($separator !== null && substr_count($value, $separator) > 1) {
                if (! preg_match('/\A[+-]?\d{1,3}(?:'.preg_quote($separator, '/').'\d{3})+\z/', $value)) {
                    return null;
                }

                $value = str_replace($separator, '', $value);
            } else {
                $value = str_replace(',', '.', $value);
            }
        }

        if (! preg_match('/\A[+-]?\d+(?:\.\d+)?\z/', $value)) {
            return null;
        }

        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '+-');
        [$integer, $decimal] = array_pad(explode('.', $unsigned, 2), 2, null);
        $integer = ltrim($integer, '0') ?: '0';
        $decimal = $decimal !== null ? rtrim($decimal, '0') : null;
        $canonical = $integer.($decimal !== null && $decimal !== '' ? ".{$decimal}" : '');

        return $negative && $canonical !== '0' ? "-{$canonical}" : $canonical;
    }

    private function expandScientificNotation(string $value): string
    {
        if (! preg_match('/\A([+-]?)(\d+)(?:\.(\d+))?[eE]([+-]?\d+)\z/', $value, $matches)) {
            return $value;
        }

        $sign = $matches[1];
        $integer = $matches[2];
        $fraction = $matches[3];
        $exponent = (int) $matches[4];
        $digits = $integer.$fraction;
        $decimalPosition = strlen($integer) + $exponent;

        if ($decimalPosition <= 0) {
            return $sign.'0.'.str_repeat('0', -$decimalPosition).$digits;
        }

        if ($decimalPosition >= strlen($digits)) {
            return $sign.$digits.str_repeat('0', $decimalPosition - strlen($digits));
        }

        return $sign.substr($digits, 0, $decimalPosition).'.'.substr($digits, $decimalPosition);
    }
}
