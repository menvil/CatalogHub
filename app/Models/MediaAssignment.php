<?php

namespace App\Models;

use Database\Factories\MediaAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'media_asset_id',
    'entity_type',
    'entity_id',
    'role',
    'position',
    'locale',
    'site_id',
    'market_id',
    'is_primary',
    'visibility',
])]
final class MediaAssignment extends Model
{
    public const ENTITY_TYPE_CENTRAL_PRODUCT = 'central_product';

    /** @use HasFactory<MediaAssignmentFactory> */
    use HasFactory;

    protected static function newFactory(): MediaAssignmentFactory
    {
        return MediaAssignmentFactory::new();
    }

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'position' => 'integer',
            'site_id' => 'integer',
            'market_id' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    /**
     * @param  Builder<MediaAssignment>  $query
     * @return Builder<MediaAssignment>
     */
    public function scopeForEntity(Builder $query, string $entityType, int $entityId): Builder
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    /**
     * @param  Builder<MediaAssignment>  $query
     * @return Builder<MediaAssignment>
     */
    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * @param  Builder<MediaAssignment>  $query
     * @return Builder<MediaAssignment>
     */
    public function scopeForLocale(Builder $query, ?string $locale): Builder
    {
        return $locale === null ? $query->whereNull('locale') : $query->where('locale', $locale);
    }
}
