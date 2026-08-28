<?php

namespace App\Actions\CentralCatalog;

use App\Data\CentralCatalog\CentralBrandLogoAssignmentResult;
use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\User;
use App\Queries\CentralCatalog\CentralBrandMediaQuery;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

final readonly class SetCentralBrandLogoAction
{
    public function __construct(
        private AuditRecorder $audit,
        private CentralBrandMediaQuery $media,
    ) {}

    public function execute(User $actor, CentralBrand $brand, MediaAsset $asset): CentralBrandLogoAssignmentResult
    {
        return DB::transaction(function () use ($actor, $brand, $asset): CentralBrandLogoAssignmentResult {
            CentralBrand::query()->lockForUpdate()->findOrFail($brand->id);
            $assignment = $this->media->primaryLogoContextQuery($brand)->lockForUpdate()->first();
            if ($assignment instanceof MediaAssignment) {
                $oldAssetId = (int) $assignment->media_asset_id;
                $wasCanonical = $assignment->position === 0 && $assignment->visibility === 'global';

                if ($oldAssetId === (int) $asset->id && $wasCanonical) {
                    return new CentralBrandLogoAssignmentResult($assignment, false);
                }

                $assignment->update(['media_asset_id' => $asset->id, 'position' => 0, 'is_primary' => true, 'visibility' => 'global']);
                $this->audit->record(AuditAction::CatalogBrandLogoAssigned, AuditContext::Central, $actor, $brand, null, [
                    'media_asset_id' => $wasCanonical ? $oldAssetId : null,
                    'role' => MediaAssignment::ROLE_BRAND_LOGO,
                ], [
                    'media_asset_id' => (int) $asset->id,
                    'role' => MediaAssignment::ROLE_BRAND_LOGO,
                ]);

                return new CentralBrandLogoAssignmentResult($assignment, true);
            }

            $assignment = MediaAssignment::query()->create(['media_asset_id' => $asset->id, 'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, 'entity_id' => $brand->id, 'role' => MediaAssignment::ROLE_BRAND_LOGO, 'position' => 0, 'locale' => null, 'site_id' => null, 'market_id' => null, 'is_primary' => true, 'visibility' => 'global']);
            $this->audit->record(AuditAction::CatalogBrandLogoAssigned, AuditContext::Central, $actor, $brand, null, [
                'media_asset_id' => null,
                'role' => MediaAssignment::ROLE_BRAND_LOGO,
            ], [
                'media_asset_id' => (int) $asset->id,
                'role' => MediaAssignment::ROLE_BRAND_LOGO,
            ]);

            return new CentralBrandLogoAssignmentResult($assignment, true);
        });
    }
}
