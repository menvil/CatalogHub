<?php

namespace App\Actions\CentralCatalog;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAssignment;
use App\Models\User;
use App\Queries\CentralCatalog\CentralBrandMediaQuery;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

final readonly class RemoveCentralBrandLogoAction
{
    public function __construct(
        private AuditRecorder $audit,
        private CentralBrandMediaQuery $media,
    ) {}

    public function __invoke(User $actor, CentralBrand $brand): bool
    {
        return DB::transaction(function () use ($actor, $brand): bool {
            CentralBrand::query()->lockForUpdate()->findOrFail($brand->id);
            $assignment = $this->media->canonicalPrimaryLogoQuery($brand)
                ->lockForUpdate()
                ->first();

            if (! $assignment instanceof MediaAssignment) {
                return false;
            }

            $oldAssetId = (int) $assignment->media_asset_id;
            $assignment->delete();
            $this->audit->record(AuditAction::CatalogBrandLogoRemoved, AuditContext::Central, $actor, $brand, null, [
                'media_asset_id' => $oldAssetId,
                'role' => MediaAssignment::ROLE_BRAND_LOGO,
            ], [
                'media_asset_id' => null,
                'role' => MediaAssignment::ROLE_BRAND_LOGO,
            ]);

            return true;
        });
    }
}
