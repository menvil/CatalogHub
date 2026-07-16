<?php

namespace App\Models;

use Database\Factories\MediaManifestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'catalog_snapshot_id',
    'media_asset_id',
    'asset_uuid',
    'original_path',
    'variants_json',
    'checksum',
    'file_size',
    'mime_type',
    'status',
    'metadata_json',
])]
final class MediaManifest extends Model
{
    /** @use HasFactory<MediaManifestFactory> */
    use HasFactory;

    protected static function newFactory(): MediaManifestFactory
    {
        return MediaManifestFactory::new();
    }

    protected function casts(): array
    {
        return [
            'variants_json' => 'array',
            'metadata_json' => 'array',
            'file_size' => 'integer',
        ];
    }

    /** @return BelongsTo<CatalogSnapshot, $this> */
    public function catalogSnapshot(): BelongsTo
    {
        return $this->belongsTo(CatalogSnapshot::class);
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }
}
