<?php

namespace App\Models;

use App\Enums\MarketOfferStatus;
use App\Enums\OfferAvailability;
use App\Enums\OfferCondition;
use App\Models\CentralCatalog\CentralProduct;
use Carbon\CarbonInterface;
use Database\Factories\MarketOfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $price
 * @property string|null $delivery_price
 * @property string|null $delivery_time
 * @property OfferAvailability $availability
 * @property OfferCondition $condition
 * @property MarketOfferStatus $status
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface $last_seen_at
 * @property CarbonInterface $last_checked_at
 */
#[Fillable([
    'market_id', 'market_merchant_id', 'central_product_id', 'price_source_id',
    'external_product_mapping_id', 'price', 'currency', 'original_price',
    'original_currency', 'availability', 'condition', 'delivery_price',
    'delivery_time', 'url', 'last_seen_at', 'last_checked_at', 'status', 'metadata',
])]
final class MarketOffer extends Model
{
    /** @use HasFactory<MarketOfferFactory> */
    use HasFactory;

    protected static function newFactory(): MarketOfferFactory
    {
        return MarketOfferFactory::new();
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'delivery_price' => 'decimal:2',
            'availability' => OfferAvailability::class,
            'condition' => OfferCondition::class,
            'status' => MarketOfferStatus::class,
            'last_seen_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Market, $this> */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /** @return BelongsTo<MarketMerchant, $this> */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(MarketMerchant::class, 'market_merchant_id');
    }

    /** @return BelongsTo<CentralProduct, $this> */
    public function centralProduct(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class);
    }

    /** @return BelongsTo<PriceSource, $this> */
    public function priceSource(): BelongsTo
    {
        return $this->belongsTo(PriceSource::class);
    }

    /** @return BelongsTo<ExternalProductMapping, $this> */
    public function externalProductMapping(): BelongsTo
    {
        return $this->belongsTo(ExternalProductMapping::class);
    }

    /** @return HasMany<PriceHistory, $this> */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }
}
