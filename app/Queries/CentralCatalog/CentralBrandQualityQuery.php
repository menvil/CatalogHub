<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Data\CentralCatalog\CentralBrandQualityReadModelData;
use App\Models\CentralCatalog\CentralBrand;

final readonly class CentralBrandQualityQuery
{
    public function __construct(private CentralBrandQualityBatchQuery $batch) {}

    public function forBrand(CentralBrand $brand): CentralBrandQualityReadModelData
    {
        $quality = $this->batch->forBrands(collect([$brand]))->get((int) $brand->getKey());

        if (! $quality instanceof CentralBrandQualityReadModelData) {
            throw new \LogicException('Brand quality projection is missing.');
        }

        return $quality;
    }
}
