<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\Enums\CentralCategoryStatus;

final readonly class CentralBrandCategoryCoverage
{
    public function __construct(
        public int $categoryId,
        public string $name,
        public CentralCategoryStatus $status,
        public int $productsCount,
    ) {}
}
