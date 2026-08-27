<?php

declare(strict_types=1);

namespace App\Actions\CentralCatalog;

use App\Data\CentralCatalog\CentralBrandLogoAssignmentResult;
use App\Jobs\Media\GenerateMediaVariantsJob;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\Media\BrandLogoPresenter;
use App\Services\Media\MediaVariantProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AssignExistingCentralBrandLogoAction
{
    public function __construct(
        private BrandLogoPresenter $logos,
        private SetCentralBrandLogoAction $setLogo,
    ) {}

    public function execute(User $actor, CentralBrand $brand, int $mediaAssetId): CentralBrandLogoAssignmentResult
    {
        return DB::transaction(function () use ($actor, $brand, $mediaAssetId): CentralBrandLogoAssignmentResult {
            $lockedAsset = MediaAsset::query()->lockForUpdate()->findOrFail($mediaAssetId);
            $allowedMimes = config('media.allowed_upload_mimes');
            if (
                $lockedAsset->type !== 'image'
                || $lockedAsset->status !== 'active'
                || ! is_array($allowedMimes)
                || ! in_array($lockedAsset->mime_type, $allowedMimes, true)
                || $this->logos->forMedia($lockedAsset)->url === null
            ) {
                throw ValidationException::withMessages([
                    'media_asset_id' => 'Choose an active, available JPEG, PNG or WebP image.',
                ]);
            }

            $result = $this->setLogo->execute($actor, $brand, $lockedAsset);
            if ($result->changed) {
                GenerateMediaVariantsJob::dispatch($lockedAsset->id, MediaVariantProfile::BrandLogo)->afterCommit();
            }

            return $result;
        });
    }
}
