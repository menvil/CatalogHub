<?php

namespace App\Models;

use Database\Factories\MediaSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'media_asset_id',
    'source_type',
    'source_url',
    'source_name',
    'license_type',
    'license_url',
    'attribution',
    'metadata',
])]
final class MediaSource extends Model
{
    /** @use HasFactory<MediaSourceFactory> */
    use HasFactory;

    protected static function newFactory(): MediaSourceFactory
    {
        return MediaSourceFactory::new();
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }
}
