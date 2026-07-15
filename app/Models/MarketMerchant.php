<?php

namespace App\Models;

use App\Enums\MarketMerchantStatus;
use Database\Factories\MarketMerchantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property MarketMerchantStatus $status
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'market_id', 'name', 'slug', 'website_url', 'logo_media_asset_id', 'status', 'metadata',
])]
final class MarketMerchant extends Model
{
    /** @use HasFactory<MarketMerchantFactory> */
    use HasFactory;

    protected static function newFactory(): MarketMerchantFactory
    {
        return MarketMerchantFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => MarketMerchantStatus::class,
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Market, $this> */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /** @return HasMany<MarketOffer, $this> */
    public function offers(): HasMany
    {
        return $this->hasMany(MarketOffer::class);
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function logoMediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'logo_media_asset_id');
    }
}
