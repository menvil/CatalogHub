<?php

namespace App\Services\Units;

use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\MeasurementUnit;

final class UnitConverter
{
    public function __construct(
        private readonly UnitResolver $unitResolver,
    ) {}

    public function convert(float|string $value, string|MeasurementUnit $from, string|MeasurementUnit $to): float
    {
        $fromUnit = $this->unitResolver->resolve($from);
        $toUnit = $this->unitResolver->resolve($to);

        if ((string) $fromUnit->dimension_id !== (string) $toUnit->dimension_id) {
            throw CannotConvertUnitException::incompatible($fromUnit->code, $toUnit->code);
        }

        return $toUnit->fromCanonical($fromUnit->toCanonical($value));
    }

    public function toCanonical(float|string $value, string|MeasurementUnit $unit): float
    {
        return $this->unitResolver->resolve($unit)->toCanonical($value);
    }

    public function fromCanonical(float|string $canonicalValue, string|MeasurementUnit $unit): float
    {
        return $this->unitResolver->resolve($unit)->fromCanonical($canonicalValue);
    }
}
