<?php

namespace App\Models;

use App\Enums\ExternalProductMappingStatus;
use App\Models\CentralCatalog\CentralProduct;
use Database\Factories\ExternalProductMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property ExternalProductMappingStatus $status
 * @property string|null $confidence
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'price_source_id', 'central_product_id', 'external_product_id', 'external_sku',
    'external_url', 'external_title', 'confidence', 'status', 'approved_at',
    'approved_by_user_id', 'rejected_at', 'rejected_by_user_id', 'notes', 'metadata',
])]
final class ExternalProductMapping extends Model
{
    /** @use HasFactory<ExternalProductMappingFactory> */
    use HasFactory;

    protected static function newFactory(): ExternalProductMappingFactory
    {
        return ExternalProductMappingFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => ExternalProductMappingStatus::class,
            'confidence' => 'decimal:4',
            'metadata' => 'array',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PriceSource, $this> */
    public function priceSource(): BelongsTo
    {
        return $this->belongsTo(PriceSource::class);
    }

    /** @return BelongsTo<CentralProduct, $this> */
    public function centralProduct(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    /** @return HasMany<MarketOffer, $this> */
    public function marketOffers(): HasMany
    {
        return $this->hasMany(MarketOffer::class);
    }
}
