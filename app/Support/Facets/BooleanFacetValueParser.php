<?php

namespace App\Support\Facets;

final class BooleanFacetValueParser
{
    public function parse(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) && in_array($value, [0, 1], true)) {
            return $value === 1;
        }

        if (! is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'yes' => true,
            '0', 'false', 'no' => false,
            default => null,
        };
    }

    public function serialize(bool $value): string
    {
        return $value ? '1' : '0';
    }
}
