<?php

namespace App\Models;

use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'type',
    'source',
    'disk',
    'original_path',
    'original_filename',
    'mime_type',
    'file_size',
    'width',
    'height',
    'checksum',
    'status',
])]
final class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (MediaAsset $asset): void {
            if (blank($asset->uuid)) {
                $asset->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): MediaAssetFactory
    {
        return MediaAssetFactory::new();
    }

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * @return HasMany<MediaVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(MediaVariant::class);
    }

    /**
     * @return HasMany<MediaAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(MediaAssignment::class);
    }

    /**
     * @return HasMany<MediaSource, $this>
     */
    public function sources(): HasMany
    {
        return $this->hasMany(MediaSource::class);
    }
}
