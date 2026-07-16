<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\MediaManifest;
use App\Models\MediaVariant;
use Generator;

final class MediaManifestJsonlExporter
{
    public function __construct(private readonly JsonlStreamWriter $writer) {}

    public function export(CatalogSnapshot $snapshot): JsonlExportResult
    {
        return $this->writer->write($snapshot, 'media_manifest', $this->rows($snapshot));
    }

    /** @return Generator<int, array<string, mixed>> */
    private function rows(CatalogSnapshot $snapshot): Generator
    {
        $assets = MediaAsset::query()
            ->with(['variants', 'sources', 'assignments'])
            ->lazyById();

        foreach ($assets as $asset) {
            $variants = $asset->variants
                ->map(fn (MediaVariant $variant): array => [
                    'type' => $variant->variant_type,
                    'locale' => $variant->locale,
                    'site_id' => $variant->site_id,
                    'market_id' => $variant->market_id,
                    'disk' => $variant->disk,
                    'path' => $variant->path,
                    'width' => $variant->width,
                    'height' => $variant->height,
                    'format' => $variant->format,
                    'file_size' => $variant->file_size,
                    'status' => $variant->status,
                ])
                ->values()
                ->all();
            $sourceUrl = $asset->sources->first()?->source_url;
            $assignments = $asset->assignments
                ->map(fn (MediaAssignment $assignment): array => [
                    'entity_type' => $assignment->entity_type,
                    'entity_id' => $assignment->entity_id,
                    'role' => $assignment->role,
                    'locale' => $assignment->locale,
                    'site_id' => $assignment->site_id,
                    'market_id' => $assignment->market_id,
                ])
                ->values()
                ->all();
            $metadata = [
                'original_disk' => $asset->disk,
                'width' => $asset->width,
                'height' => $asset->height,
                'source_url' => $sourceUrl,
                'assignments' => $assignments,
            ];

            MediaManifest::query()->updateOrCreate(
                [
                    'catalog_snapshot_id' => $snapshot->getKey(),
                    'media_asset_id' => $asset->getKey(),
                ],
                [
                    'asset_uuid' => $asset->uuid,
                    'original_path' => $asset->original_path,
                    'variants_json' => $variants,
                    'checksum' => $asset->checksum,
                    'file_size' => $asset->file_size,
                    'mime_type' => $asset->mime_type,
                    'status' => 'pending',
                    'metadata_json' => $metadata,
                ],
            );

            yield [
                'asset_id' => $asset->getKey(),
                'asset_uuid' => $asset->uuid,
                'original_path' => $asset->original_path,
                'original_disk' => $asset->disk,
                'variants' => $variants,
                'checksum' => $asset->checksum,
                'file_size' => $asset->file_size,
                'mime_type' => $asset->mime_type,
                'width' => $asset->width,
                'height' => $asset->height,
                'source_url' => $sourceUrl,
                'status' => $asset->status,
                'assignments' => $assignments,
                'created_at' => $asset->created_at?->toISOString(),
                'updated_at' => $asset->updated_at?->toISOString(),
            ];
        }
    }
}
