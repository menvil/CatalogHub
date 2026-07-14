<?php

namespace App\Data\Facets;

final class FacetFilterSet
{
    /**
     * @param  array<string, mixed>  $values
     * @param  list<AppliedFacetFilter>  $appliedFilters
     */
    private function __construct(
        private readonly array $values,
        private readonly array $appliedFilters = [],
    ) {}

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            $key = trim((string) $key);
            $value = self::normalizeValue($value);

            if ($key !== '' && $value !== null && $value !== '' && $value !== []) {
                $normalized[$key] = $value;
            }
        }

        return new self($normalized);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    /** @param list<AppliedFacetFilter> $filters */
    public function withAppliedFilters(array $filters): self
    {
        return new self($this->values, $filters);
    }

    /** @return list<AppliedFacetFilter> */
    public function appliedFilters(): array
    {
        return $this->appliedFilters;
    }

    private static function normalizeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_array($value)) {
            return array_values(array_filter(
                array_map(self::normalizeValue(...), $value),
                fn (mixed $item): bool => $item !== null && $item !== '',
            ));
        }

        return $value;
    }
}
