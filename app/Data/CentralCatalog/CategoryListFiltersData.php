<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

final readonly class CategoryListFiltersData
{
    public function __construct(
        public ?string $search = null,
        public ?string $status = null,
        public ?string $schemaStatus = null,
        public ?int $level = null,
        public ?int $localeId = null,
        public ?int $siteId = null,
        public ?string $translation = null,
        public string $sort = 'name',
        public string $direction = 'asc',
        public int $perPage = 20,
    ) {}

    public function activeCount(): int
    {
        return count(array_filter([$this->search, $this->status, $this->schemaStatus, $this->level, $this->localeId, $this->siteId, $this->translation], static fn ($value): bool => $value !== null));
    }

    public function hasConstraints(): bool
    {
        return $this->activeCount() > 0;
    }
}
