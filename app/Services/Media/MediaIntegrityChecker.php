<?php

namespace App\Services\Media;

use App\Models\MediaAsset;
use App\Models\MediaManifest;
use App\Models\MediaVariant;
use Illuminate\Support\Facades\Storage;

final class MediaIntegrityChecker
{
    public function run(): MediaIntegrityCheckResult
    {
        $assetCount = 0;
        $checkedFileCount = 0;
        $missingPaths = [];

        foreach (MediaAsset::query()->with('variants')->lazyById() as $asset) {
            $assetCount++;
            $assetMissingPaths = [];
            $checkedFileCount++;

            $originalExists = Storage::disk($asset->disk)->exists($asset->original_path);

            if (! $originalExists) {
                $path = "{$asset->disk}:{$asset->original_path}";
                $missingPaths[] = $path;
                $assetMissingPaths[] = $path;
            }

            $variants = $asset->variants
                ->map(function (MediaVariant $variant) use (&$checkedFileCount, &$missingPaths, &$assetMissingPaths): array {
                    $checkedFileCount++;
                    $exists = Storage::disk($variant->disk)->exists($variant->path);

                    if (! $exists) {
                        $path = "{$variant->disk}:{$variant->path}";
                        $missingPaths[] = $path;
                        $assetMissingPaths[] = $path;
                    }

                    return [
                        'id' => $variant->getKey(),
                        'type' => $variant->variant_type,
                        'disk' => $variant->disk,
                        'path' => $variant->path,
                        'exists' => $exists,
                    ];
                })
                ->values()
                ->all();
            $missingVariantCount = collect($variants)->where('exists', false)->count();

            MediaManifest::query()->updateOrCreate(
                [
                    'catalog_snapshot_id' => null,
                    'media_asset_id' => $asset->getKey(),
                ],
                [
                    'asset_uuid' => $asset->uuid,
                    'original_path' => $asset->original_path,
                    'variants_json' => $variants,
                    'checksum' => $asset->checksum,
                    'file_size' => $asset->file_size,
                    'mime_type' => $asset->mime_type,
                    'status' => $assetMissingPaths === [] ? 'verified' : 'missing',
                    'metadata_json' => [
                        'original_disk' => $asset->disk,
                        'missing_original' => ! $originalExists,
                        'missing_variant_count' => $missingVariantCount,
                        'missing_paths' => $assetMissingPaths,
                        'last_checked_at' => now()->toISOString(),
                    ],
                ],
            );
        }

        return new MediaIntegrityCheckResult($assetCount, $checkedFileCount, $missingPaths);
    }
}
