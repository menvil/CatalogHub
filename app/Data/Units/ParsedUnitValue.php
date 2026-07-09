<?php

namespace App\Data\Units;

final readonly class ParsedUnitValue
{
    public function __construct(
        public float $value,
        public string $unit_code,
        public string $raw_unit,
        public string $raw_value,
    ) {}
}
