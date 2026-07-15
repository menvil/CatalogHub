<?php

namespace App\Models;

use App\Enums\OfferAvailability;
use App\Enums\OfferCondition;
use Database\Factories\PriceHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $price
 * @property string|null $delivery_price
 * @property OfferAvailability $availability
 * @property OfferCondition|null $condition
 * @property array<string, mixed>|null $source_snapshot_json
 */
#[Fillable([
    'market_offer_id', 'price', 'currency', 'availability', 'condition',
    'delivery_price', 'checked_at', 'source_snapshot_json',
])]
final class PriceHistory extends Model
{
    /** @use HasFactory<PriceHistoryFactory> */
    use HasFactory;

    protected $table = 'price_history';

    protected static function newFactory(): PriceHistoryFactory
    {
        return PriceHistoryFactory::new();
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'delivery_price' => 'decimal:2',
            'availability' => OfferAvailability::class,
            'condition' => OfferCondition::class,
            'checked_at' => 'datetime',
            'source_snapshot_json' => 'array',
        ];
    }

    /** @return BelongsTo<MarketOffer, $this> */
    public function marketOffer(): BelongsTo
    {
        return $this->belongsTo(MarketOffer::class);
    }
}
