<?php

namespace App\Jobs\Media;

use App\Services\Media\MediaVariantGenerator;
use App\Services\Media\MediaVariantProfile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateMediaVariantsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $mediaAssetId, public MediaVariantProfile $profile) {}

    public function handle(MediaVariantGenerator $generator): void
    {
        $generator->generateForAsset($this->mediaAssetId, $this->profile);
    }
}
