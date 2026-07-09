<?php

namespace App\Services\Units;

use App\Data\Units\ParsedUnitValue;
use App\Exceptions\Units\CannotParseUnitException;
use App\Models\MeasurementUnit;
use Illuminate\Support\Collection;

final class UnitParser
{
    public function parse(string $raw): ParsedUnitValue
    {
        if (! preg_match('/^\s*(?<value>[+-]?\d+(?:[.,]\d+)?)\s*(?<unit>.+?)\s*$/u', $raw, $matches)) {
            throw CannotParseUnitException::invalidValue($raw);
        }

        $rawValue = $matches['value'];
        $rawUnit = trim($matches['unit']);
        $unit = $this->findUnit($rawUnit);

        if (! $unit instanceof MeasurementUnit) {
            throw CannotParseUnitException::unknownUnit($rawUnit);
        }

        return new ParsedUnitValue(
            value: (float) str_replace(',', '.', $rawValue),
            unit_code: $unit->code,
            raw_unit: $rawUnit,
            raw_value: $rawValue,
        );
    }

    private function findUnit(string $rawUnit): ?MeasurementUnit
    {
        $needle = $this->normalizeAlias($rawUnit);

        return $this->units()->first(function (MeasurementUnit $unit) use ($needle): bool {
            foreach ($this->aliasesFor($unit) as $alias) {
                if ($this->normalizeAlias($alias) === $needle) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * @return Collection<int, MeasurementUnit>
     */
    private function units(): Collection
    {
        return MeasurementUnit::query()
            ->where('is_active', true)
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private function aliasesFor(MeasurementUnit $unit): array
    {
        return array_values(array_unique(array_filter([
            $unit->code,
            $unit->symbol,
            $unit->name,
            ...($unit->aliases_json ?? []),
        ], static fn (?string $alias): bool => $alias !== null && $alias !== '')));
    }

    private function normalizeAlias(string $alias): string
    {
        return mb_strtolower(trim($alias));
    }
}
