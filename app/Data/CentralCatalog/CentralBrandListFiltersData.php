<?php

namespace App\Data\CentralCatalog;

final readonly class CentralBrandListFiltersData
{
    public function __construct(
        public ?string $search,
        public ?string $status,
        public string $sort = 'name',
        public string $direction = 'asc',
    ) {}
}
