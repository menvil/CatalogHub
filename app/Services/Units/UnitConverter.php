<?php

namespace App\Services\Units;

use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\MeasurementUnit;

final class UnitConverter
{
    public function convert(float|string $value, string|MeasurementUnit $from, string|MeasurementUnit $to): float
    {
        $fromUnit = $this->resolveUnit($from);
        $toUnit = $this->resolveUnit($to);

        if ($fromUnit->dimension_id !== $toUnit->dimension_id) {
            throw CannotConvertUnitException::incompatible($fromUnit->code, $toUnit->code);
        }

        return $toUnit->fromCanonical($fromUnit->toCanonical($value));
    }

    public function toCanonical(float|string $value, string|MeasurementUnit $unit): float
    {
        return $this->resolveUnit($unit)->toCanonical($value);
    }

    public function fromCanonical(float|string $canonicalValue, string|MeasurementUnit $unit): float
    {
        return $this->resolveUnit($unit)->fromCanonical($canonicalValue);
    }

    private function resolveUnit(string|MeasurementUnit $unit): MeasurementUnit
    {
        if ($unit instanceof MeasurementUnit) {
            return $unit;
        }

        $resolved = MeasurementUnit::query()
            ->where('code', $unit)
            ->first();

        if (! $resolved instanceof MeasurementUnit) {
            throw CannotConvertUnitException::unknownUnit($unit);
        }

        return $resolved;
    }
}
