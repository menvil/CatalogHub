<?php

namespace App\Actions\CentralCatalog;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAssignment;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

final readonly class RemoveCentralBrandLogoAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function __invoke(User $actor, CentralBrand $brand): void
    {
        DB::transaction(function () use ($actor, $brand): void {
            CentralBrand::query()->lockForUpdate()->findOrFail($brand->id);
            $assignment = MediaAssignment::query()->forEntity(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id)->forRole(MediaAssignment::ROLE_BRAND_LOGO)->whereNull('locale')->whereNull('site_id')->whereNull('market_id')->first();

            if (! $assignment instanceof MediaAssignment) {
                return;
            }

            $oldAssetId = (int) $assignment->media_asset_id;
            $assignment->delete();
            $this->audit->record(AuditAction::CatalogBrandLogoRemoved, AuditContext::Central, $actor, $brand, null, ['media_asset_id' => $oldAssetId], ['media_asset_id' => null]);
        });
    }
}
