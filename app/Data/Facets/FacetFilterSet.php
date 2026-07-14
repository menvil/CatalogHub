<?php

namespace App\Data\Facets;

use App\Enums\PublicProductSort;

final class FacetFilterSet
{
    /**
     * @param  array<string, mixed>  $values
     * @param  list<AppliedFacetFilter>  $appliedFilters
     */
    private function __construct(
        private array $values,
        private array $appliedFilters = [],
    ) {}

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            $key = trim((string) $key);
            $value = self::normalizeValue($value);

            if (self::validKey($key) && $value !== null && $value !== '' && $value !== []) {
                $normalized[$key] = $value;
            }
        }

        return new self($normalized);
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        unset($query['page'], $query['per_page']);
        $normalized = [];

        foreach ($query as $key => $value) {
            $key = trim((string) $key);

            if (! self::validKey($key)) {
                continue;
            }

            if (is_array($value) || (is_string($value) && str_contains($value, ','))) {
                $values = is_array($value) ? $value : explode(',', $value);
                $values = self::normalizeList($values);

                if ($values !== []) {
                    $normalized[$key] = $values;
                }

                continue;
            }

            $value = self::normalizeQueryScalar($value);

            if ($key === 'sort') {
                $value = PublicProductSort::fromInput($value)->value;
            } elseif (is_string($value)) {
                $value = match (strtolower($value)) {
                    'true', 'yes' => '1',
                    'false', 'no' => '0',
                    default => $value,
                };
            }

            if ($value !== null && $value !== '') {
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

    /** @return array<string, string> */
    public function toQueryArray(): array
    {
        $query = [];

        foreach ($this->values as $key => $value) {
            if (in_array($key, ['page', 'per_page'], true)) {
                continue;
            }

            if (is_array($value)) {
                $value = implode(',', self::normalizeList($value));
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif (is_scalar($value)) {
                $value = self::normalizeQueryScalar($value);
            } else {
                continue;
            }

            if ($value !== null && $value !== '') {
                $query[$key] = $value;
            }
        }

        ksort($query, SORT_STRING);

        return $query;
    }

    public function hasActiveFilters(): bool
    {
        return collect($this->values)
            ->except(['sort', 'page', 'per_page'])
            ->isNotEmpty();
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

    public function recordAppliedFilter(AppliedFacetFilter $filter): void
    {
        $this->appliedFilters[] = $filter;
    }

    public function clearAppliedFilters(): void
    {
        $this->appliedFilters = [];
    }

    public function replace(string $key, mixed $value): void
    {
        $value = self::normalizeValue($value);

        if (! self::validKey($key) || $value === null || $value === '' || $value === []) {
            $this->forget($key);

            return;
        }

        $this->values[$key] = $value;
    }

    public function forget(string ...$keys): void
    {
        foreach ($keys as $key) {
            unset($this->values[$key]);
        }
    }

    /** @param list<string> $keys */
    public function retain(array $keys): void
    {
        $this->values = array_intersect_key($this->values, array_flip($keys));
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

    private static function validKey(string $key): bool
    {
        return preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/', $key) === 1;
    }

    /** @param array<mixed> $values @return list<string> */
    private static function normalizeList(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $value = self::normalizeQueryScalar($value);

            if ($value !== null && $value !== '') {
                $normalized[] = $value;
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized, SORT_STRING);

        return $normalized;
    }

    private static function normalizeQueryScalar(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $value = trim((string) $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';

        return mb_substr($value, 0, 100);
    }
}
