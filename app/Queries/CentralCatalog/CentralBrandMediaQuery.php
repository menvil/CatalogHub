<?php

namespace App\Queries\CentralCatalog;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Services\Media\MediaResolver;

final readonly class CentralBrandMediaQuery
{
    public function __construct(private MediaResolver $resolver) {}

    public function logoFor(CentralBrand $brand): ?MediaAsset
    {
        return $this->resolver->resolve(
            MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            $brand->id,
            MediaAssignment::ROLE_BRAND_LOGO,
        );
    }
}
