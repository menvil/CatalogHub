<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class CentralBrandListReadModelData
{
    /**
     * @param  LengthAwarePaginator<int, CentralBrandListRow>  $brands
     * @param  Collection<int, CentralBrandQualityReadModelData>  $healthByBrandId
     */
    public function __construct(
        public LengthAwarePaginator $brands,
        public CentralBrandListSummary $summary,
        public Collection $healthByBrandId,
    ) {}
}
