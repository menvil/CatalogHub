<?php

namespace App\Actions\CentralCatalog;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use Illuminate\Support\Facades\DB;

final class SetCentralBrandLogoAction
{
    public function execute(CentralBrand $brand, MediaAsset $asset): MediaAssignment
    {
        return DB::transaction(function () use ($brand, $asset): MediaAssignment {
            CentralBrand::query()->lockForUpdate()->findOrFail($brand->id);
            $assignment = MediaAssignment::query()->forEntity(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id)->forRole(MediaAssignment::ROLE_BRAND_LOGO)->whereNull('locale')->whereNull('site_id')->whereNull('market_id')->first();
            if ($assignment instanceof MediaAssignment) {
                $assignment->update(['media_asset_id' => $asset->id, 'position' => 0, 'is_primary' => true, 'visibility' => 'global']);

                return $assignment;
            }

            return MediaAssignment::query()->create(['media_asset_id' => $asset->id, 'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, 'entity_id' => $brand->id, 'role' => MediaAssignment::ROLE_BRAND_LOGO, 'position' => 0, 'locale' => null, 'site_id' => null, 'market_id' => null, 'is_primary' => true, 'visibility' => 'global']);
        });
    }
}
