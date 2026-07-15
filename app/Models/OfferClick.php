<?php

namespace App\Models;

use App\Models\CentralCatalog\CentralProduct;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $central_product_id
 * @property int|null $merchant_id
 * @property string|null $session_id
 * @property string|null $ip_hash
 * @property string|null $user_agent_hash
 * @property CarbonInterface $clicked_at
 */
#[Fillable([
    'site_id', 'market_offer_id', 'central_product_id', 'merchant_id', 'user_id',
    'session_id', 'ip_hash', 'user_agent_hash', 'clicked_at',
])]
final class OfferClick extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['clicked_at' => 'datetime'];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<MarketOffer, $this> */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(MarketOffer::class, 'market_offer_id');
    }

    /** @return BelongsTo<CentralProduct, $this> */
    public function centralProduct(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class);
    }

    /** @return BelongsTo<MarketMerchant, $this> */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(MarketMerchant::class, 'merchant_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
