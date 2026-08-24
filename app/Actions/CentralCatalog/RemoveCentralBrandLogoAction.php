<?php

namespace App\Actions\CentralCatalog;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAssignment;

final class RemoveCentralBrandLogoAction
{
    public function execute(CentralBrand $brand): void
    {
        MediaAssignment::query()->forEntity(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id)->forRole(MediaAssignment::ROLE_BRAND_LOGO)->whereNull('locale')->whereNull('site_id')->whereNull('market_id')->delete();
    }
}
