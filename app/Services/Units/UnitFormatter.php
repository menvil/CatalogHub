<?php

namespace App\Services\Units;

use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\MeasurementUnit;

final class UnitFormatter
{
    public function format(float|string $value, string|MeasurementUnit $unit, ?int $decimals = null, ?string $locale = null): string
    {
        $resolvedUnit = $this->resolveUnit($unit);
        $precision = $decimals ?? $resolvedUnit->precision_default;
        $number = number_format((float) $value, $precision, '.', '');

        if ($decimals === null) {
            $number = $this->trimTrailingZeros($number);
        }

        if ($this->usesCommaDecimal($locale)) {
            $number = str_replace('.', ',', $number);
        }

        return trim("{$number} {$resolvedUnit->symbol}");
    }

    private function resolveUnit(string|MeasurementUnit $unit): MeasurementUnit
    {
        if ($unit instanceof MeasurementUnit) {
            return $unit;
        }

        $resolved = MeasurementUnit::query()->where('code', $unit)->first();

        if (! $resolved instanceof MeasurementUnit) {
            throw CannotConvertUnitException::unknownUnit($unit);
        }

        return $resolved;
    }

    private function trimTrailingZeros(string $number): string
    {
        if (! str_contains($number, '.')) {
            return $number;
        }

        return rtrim(rtrim($number, '0'), '.');
    }

    private function usesCommaDecimal(?string $locale): bool
    {
        if ($locale === null) {
            return false;
        }

        $language = mb_strtolower(str($locale)->before('_')->before('-')->toString());

        return in_array($language, ['bg', 'de', 'fr', 'es', 'it'], true);
    }
}
