<?php

namespace App\Jobs\Media;

use App\Services\Media\MediaVariantGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateMediaVariantsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $mediaAssetId) {}

    public function handle(?MediaVariantGenerator $generator = null): void
    {
        try {
            ($generator ?? app(MediaVariantGenerator::class))->generateForAsset($this->mediaAssetId);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
