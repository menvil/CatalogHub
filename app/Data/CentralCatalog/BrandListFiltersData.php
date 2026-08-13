<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

final readonly class BrandListFiltersData
{
    public function __construct(
        public ?string $search,
        public ?string $status,
        public string $sort,
        public string $direction,
        public int $perPage,
    ) {}

    public function hasConstraints(): bool
    {
        return $this->search !== null || $this->status !== null;
    }
}
