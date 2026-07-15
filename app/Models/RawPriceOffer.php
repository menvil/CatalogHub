<?php

namespace App\Models;

use Database\Factories\RawPriceOfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed> $raw_payload_json
 * @property array<string, mixed>|null $normalized_payload_json
 */
#[Fillable([
    'price_source_id', 'price_source_sync_log_id', 'external_product_id',
    'external_sku', 'external_title', 'raw_payload_json', 'normalized_payload_json',
    'status', 'error_message', 'fetched_at',
])]
final class RawPriceOffer extends Model
{
    /** @use HasFactory<RawPriceOfferFactory> */
    use HasFactory;

    protected static function newFactory(): RawPriceOfferFactory
    {
        return RawPriceOfferFactory::new();
    }

    protected function casts(): array
    {
        return [
            'raw_payload_json' => 'array',
            'normalized_payload_json' => 'array',
            'fetched_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PriceSource, $this> */
    public function priceSource(): BelongsTo
    {
        return $this->belongsTo(PriceSource::class);
    }

    /** @return BelongsTo<PriceSourceSyncLog, $this> */
    public function syncLog(): BelongsTo
    {
        return $this->belongsTo(PriceSourceSyncLog::class, 'price_source_sync_log_id');
    }
}
