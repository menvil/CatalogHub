<?php

namespace App\Services\Units;

use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\MeasurementUnit;

final class UnitResolver
{
    public function resolve(string|MeasurementUnit $unit): MeasurementUnit
    {
        if ($unit instanceof MeasurementUnit) {
            if (! $unit->is_active) {
                throw CannotConvertUnitException::inactiveUnit($unit->code);
            }

            return $unit;
        }

        $resolved = MeasurementUnit::query()
            ->active()
            ->where('code', $unit)
            ->first();

        if (! $resolved instanceof MeasurementUnit) {
            throw CannotConvertUnitException::unknownUnit($unit);
        }

        return $resolved;
    }
}
