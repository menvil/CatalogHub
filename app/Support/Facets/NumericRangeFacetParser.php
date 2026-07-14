<?php

namespace App\Support\Facets;

final class NumericRangeFacetParser
{
    /** @return array{min: float|null, max: float|null}|null */
    public function parse(mixed $minimum, mixed $maximum): ?array
    {
        $minimum = $this->number($minimum);
        $maximum = $this->number($maximum);

        if ($minimum === null && $maximum === null) {
            return null;
        }

        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            [$minimum, $maximum] = [$maximum, $minimum];
        }

        return ['min' => $minimum, 'max' => $maximum];
    }

    public function serialize(float $value): string
    {
        if ($value == 0.0) {
            return '0';
        }

        return sprintf('%.14g', $value);
    }

    private function number(mixed $value): ?float
    {
        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return is_finite($number) ? $number : null;
    }
}
