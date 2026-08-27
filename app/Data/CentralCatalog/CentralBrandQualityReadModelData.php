<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\Services\Media\BrandLogoPresentation;

final readonly class CentralBrandQualityReadModelData
{
    public function __construct(
        public CentralBrandQualitySummary $summary,
        public BrandLogoPresentation $logo,
    ) {}
}
