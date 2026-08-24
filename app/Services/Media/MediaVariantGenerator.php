<?php

namespace App\Services\Media;

use App\Models\MediaAsset;
use App\Models\MediaVariant;

final readonly class MediaVariantGenerator
{
    public function __construct(private MediaStorage $storage, private MediaPathGenerator $paths, private MediaVariantSpecificationRegistry $registry, private ImageVariantProcessor $processor) {}

    public function generateForAsset(int $assetId, MediaVariantProfile $profile): void
    {
        $asset = MediaAsset::query()->findOrFail($assetId);
        $failures = [];
        foreach ($this->registry->forProfile($profile) as $spec) {
            try {
                $this->generate($asset, $spec);
            } catch (\Throwable $exception) {
                $failures[] = $exception;
            }
        }
        if ($failures !== []) {
            throw new MediaVariantGenerationException("One or more {$profile->value} variants failed.", previous: $failures[0]);
        }
    }

    private function generate(MediaAsset $asset, MediaVariantSpecification $spec): void
    {
        $path = $this->paths->variant($asset->uuid, $spec->name, $spec->format);
        $existing = MediaVariant::query()->where(['media_asset_id' => $asset->id, 'variant_type' => $spec->name])->whereNull('locale')->whereNull('site_id')->whereNull('market_id')->first();
        if ($existing?->status === 'ready' && $existing->transform_hash === $spec->transformHash() && $this->storage->exists($existing->disk, $existing->path)) {
            return;
        }
        try {
            $result = $this->processor->process($this->storage->read($asset->disk, $asset->original_path), $spec);
            $this->storage->putContents($asset->disk, $path, $result['bytes']);
            MediaVariant::query()->updateOrCreate(['media_asset_id' => $asset->id, 'variant_type' => $spec->name, 'locale' => null, 'site_id' => null, 'market_id' => null], ['disk' => $asset->disk, 'path' => $path, 'width' => $result['width'], 'height' => $result['height'], 'format' => $spec->format, 'file_size' => strlen($result['bytes']), 'quality' => $spec->quality, 'transform_hash' => $spec->transformHash(), 'status' => 'ready']);
        } catch (\Throwable $e) {
            MediaVariant::query()->updateOrCreate(['media_asset_id' => $asset->id, 'variant_type' => $spec->name, 'locale' => null, 'site_id' => null, 'market_id' => null], ['disk' => $asset->disk, 'path' => $path, 'format' => $spec->format, 'quality' => $spec->quality, 'transform_hash' => $spec->transformHash(), 'status' => 'failed']);
            throw $e;
        }
    }
}
