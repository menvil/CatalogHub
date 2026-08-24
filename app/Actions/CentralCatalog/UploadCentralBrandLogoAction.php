<?php

namespace App\Actions\CentralCatalog;

use App\Jobs\Media\GenerateMediaVariantsJob;
use App\Models\CentralCatalog\CentralBrand;
use App\Services\Media\MediaService;
use Illuminate\Http\UploadedFile;

final readonly class UploadCentralBrandLogoAction
{
    public function __construct(private MediaService $media, private SetCentralBrandLogoAction $setLogo) {}

    public function execute(CentralBrand $brand, UploadedFile $file): void
    {
        $asset = $this->media->uploadOriginal($file);
        $this->setLogo->execute($brand, $asset);
        GenerateMediaVariantsJob::dispatch($asset->id)->afterCommit();
    }
}
