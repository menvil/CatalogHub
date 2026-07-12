<?php

namespace App\Models;

use Database\Factories\MediaVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'media_asset_id',
    'variant_type',
    'locale',
    'site_id',
    'market_id',
    'disk',
    'path',
    'width',
    'height',
    'format',
    'file_size',
    'quality',
    'transform_hash',
    'status',
])]
final class MediaVariant extends Model
{
    /** @use HasFactory<MediaVariantFactory> */
    use HasFactory;

    protected static function newFactory(): MediaVariantFactory
    {
        return MediaVariantFactory::new();
    }

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'file_size' => 'integer',
            'quality' => 'integer',
            'site_id' => 'integer',
            'market_id' => 'integer',
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
