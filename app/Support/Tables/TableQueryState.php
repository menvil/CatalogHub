<?php

declare(strict_types=1);

namespace App\Support\Tables;

use InvalidArgumentException;

final readonly class TableQueryState
{
    /**
     * @param  list<string>  $allowedSorts
     * @param  array<string, string>  $filters
     */
    private function __construct(
        public ?string $search,
        public string $sort,
        public string $direction,
        public int $page,
        private array $allowedSorts,
        public array $filters,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @param  list<string>  $allowedSorts
     * @param  list<string>  $filterKeys
     */
    public static function from(array $query, array $allowedSorts, string $defaultSort, array $filterKeys = []): self
    {
        if ($allowedSorts === [] || ! in_array($defaultSort, $allowedSorts, true)) {
            throw new InvalidArgumentException('The default table sort must be present in the allowed sort list.');
        }

        $requestedSort = is_string($query['sort'] ?? null) ? $query['sort'] : '';
        $sort = in_array($requestedSort, $allowedSorts, true) ? $requestedSort : $defaultSort;
        $direction = in_array($query['direction'] ?? null, ['asc', 'desc'], true)
            ? $query['direction']
            : 'asc';
        $search = is_string($query['q'] ?? null) ? trim($query['q']) : '';
        $page = filter_var($query['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $filters = [];

        foreach ($filterKeys as $filterKey) {
            if (in_array($filterKey, ['q', 'sort', 'direction', 'page'], true)) {
                continue;
            }

            $value = $query[$filterKey] ?? null;

            if (is_scalar($value) && trim((string) $value) !== '') {
                $filters[$filterKey] = trim((string) $value);
            }
        }

        return new self($search !== '' ? $search : null, $sort, $direction, $page, $allowedSorts, $filters);
    }

    /** @return array<string, string> */
    public function forSort(string $sort): array
    {
        $this->assertAllowedSort($sort);

        return $this->query(
            sort: $sort,
            direction: $sort === $this->sort && $this->direction === 'asc' ? 'desc' : 'asc',
        );
    }

    /** @return array<string, string|int> */
    public function forPage(int $page): array
    {
        return $this->query(page: max(1, $page));
    }

    public function directionFor(string $sort): string
    {
        $this->assertAllowedSort($sort);

        return $sort === $this->sort ? $this->direction : 'asc';
    }

    /** @return array<string, string|int> */
    private function query(?string $sort = null, ?string $direction = null, ?int $page = null): array
    {
        $query = [];

        if ($this->search !== null) {
            $query['q'] = $this->search;
        }

        $query['sort'] = $sort ?? $this->sort;
        $query['direction'] = $direction ?? $this->direction;

        if ($page !== null && $page > 1) {
            $query['page'] = $page;
        }

        return [...$query, ...$this->filters];
    }

    private function assertAllowedSort(string $sort): void
    {
        if (! in_array($sort, $this->allowedSorts, true)) {
            throw new InvalidArgumentException("Unsupported table sort [{$sort}].");
        }
    }
}
