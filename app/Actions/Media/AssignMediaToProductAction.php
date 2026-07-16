<?php

namespace App\Actions\Media;

use App\Data\Media\AssignMediaToProductData;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MediaAssignment;
use App\Support\Media\MediaAssignmentRoles;
use Illuminate\Support\Facades\DB;

final class AssignMediaToProductAction
{
    public function handle(CentralProduct $product, AssignMediaToProductData $data): MediaAssignment
    {
        return DB::transaction(function () use ($data, $product): MediaAssignment {
            $scope = [
                'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_PRODUCT,
                'entity_id' => (int) $product->getKey(),
                'role' => $data->role,
                'locale' => $data->locale,
                'site_id' => $data->siteId,
                'market_id' => $data->marketId,
            ];

            $lockedAssignments = MediaAssignment::query()
                ->forEntity(MediaAssignment::ENTITY_TYPE_CENTRAL_PRODUCT, (int) $product->getKey())
                ->forRole($data->role)
                ->lockForUpdate()
                ->get();

            if (MediaAssignmentRoles::isSingular($data->role)) {
                MediaAssignment::query()->where($scope)->delete();
            }

            return MediaAssignment::query()->create($scope + [
                'media_asset_id' => $data->mediaAssetId,
                'position' => ((int) $lockedAssignments->max('position')) + 1,
                'is_primary' => MediaAssignmentRoles::isSingular($data->role),
                'visibility' => 'global',
            ]);
        });
    }
}
