<?php

namespace App\Services\Units;

use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\MeasurementUnit;

final class UnitResolver
{
    public function resolve(string|MeasurementUnit $unit): MeasurementUnit
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
