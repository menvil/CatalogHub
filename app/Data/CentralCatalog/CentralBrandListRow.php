<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\Models\CentralCatalog\CentralBrand;

final readonly class CentralBrandListRow
{
    public function __construct(
        public CentralBrand $brand,
        public int $categoryCount,
        public CentralBrandQualityReadModelData $health,
    ) {}
}
