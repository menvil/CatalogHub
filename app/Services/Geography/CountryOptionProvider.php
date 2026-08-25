<?php

declare(strict_types=1);

namespace App\Services\Geography;

use App\Models\Geography\Country;
use Collator;

final readonly class CountryOptionProvider
{
    public function __construct(private CountryNameResolver $names) {}

    /** @return list<array{value: string, label: string, search: string}> */
    public function options(?int $selectedCountryId, string $locale): array
    {
        $countries = Country::query()
            ->with('translations')
            ->where(function ($query) use ($selectedCountryId): void {
                $query->active();

                if ($selectedCountryId !== null) {
                    $query->orWhere($query->getModel()->getQualifiedKeyName(), $selectedCountryId);
                }
            })
            ->get();

        $options = $countries->map(function (Country $country) use ($locale): array {
            $resolvedName = $this->names->nameFor($country, $locale);
            $label = "{$resolvedName} ({$country->alpha2})";

            if (! $country->is_active) {
                $label .= ' — Inactive';
            }

            return [
                'value' => (string) $country->getKey(),
                'label' => $label,
                'search' => implode(' ', [$resolvedName, $country->canonical_name, $country->alpha2, $country->alpha3]),
                'sort_name' => $resolvedName,
                'sort_code' => $country->alpha2,
            ];
        })->all();

        $collator = new Collator($locale);
        usort($options, static function (array $left, array $right) use ($collator): int {
            $comparison = $collator->compare($left['sort_name'], $right['sort_name']);

            if ($comparison === false) {
                $comparison = strcmp($left['sort_name'], $right['sort_name']);
            }

            return $comparison !== 0 ? $comparison : $left['sort_code'] <=> $right['sort_code'];
        });

        return array_map(
            static fn (array $option): array => [
                'value' => $option['value'],
                'label' => $option['label'],
                'search' => $option['search'],
            ],
            $options,
        );
    }
}
