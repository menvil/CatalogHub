<?php

namespace App\Queries\CentralCatalog;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;

final readonly class CentralBrandMediaQuery
{
    public function logoFor(CentralBrand $brand): ?MediaAsset
    {
        return $this->primaryLogoAssignmentFor($brand)?->asset;
    }

    public function primaryLogoAssignmentFor(CentralBrand $brand): ?MediaAssignment
    {
        return MediaAssignment::query()
            ->with('asset.variants')
            ->forEntity(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, (int) $brand->getKey())
            ->forRole(MediaAssignment::ROLE_BRAND_LOGO)
            ->whereNull('locale')
            ->whereNull('site_id')
            ->whereNull('market_id')
            ->where('is_primary', true)
            ->where('visibility', 'global')
            ->orderBy('position')
            ->orderBy('id')
            ->first();
    }
}
