<?php

namespace App\Actions\CentralCatalog;

use App\Data\CentralCatalog\CentralBrandLogoAssignmentResult;
use App\Jobs\Media\GenerateMediaVariantsJob;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Services\Media\MediaService;
use App\Services\Media\MediaStorage;
use App\Services\Media\MediaVariantProfile;
use Illuminate\Http\UploadedFile;
use Throwable;

final readonly class UploadCentralBrandLogoAction
{
    public function __construct(
        private MediaService $media,
        private MediaStorage $storage,
        private SetCentralBrandLogoAction $setLogo,
    ) {}

    public function __invoke(User $actor, CentralBrand $brand, UploadedFile $file): CentralBrandLogoAssignmentResult
    {
        $asset = $this->media->uploadOriginal($file);
        $createdAsset = $asset->wasRecentlyCreated;

        try {
            $result = $this->setLogo->execute($actor, $brand, $asset);
        } catch (Throwable $exception) {
            if ($createdAsset) {
                try {
                    $asset->delete();
                } catch (Throwable) {
                    // Preserve the assignment exception.
                }

                try {
                    $this->storage->delete((string) $asset->disk, (string) $asset->original_path);
                } catch (Throwable) {
                    // Preserve the assignment exception.
                }
            }

            throw $exception;
        }

        if ($result->changed) {
            GenerateMediaVariantsJob::dispatch($asset->id, MediaVariantProfile::BrandLogo)->afterCommit();
        }

        return $result;
    }
}
