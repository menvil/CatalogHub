<?php

namespace App\Models;

use App\Enums\PriceSourceSyncStatus;
use Database\Factories\PriceSourceSyncLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property PriceSourceSyncStatus $status
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'price_source_id', 'status', 'started_at', 'finished_at', 'items_fetched',
    'items_normalized', 'items_matched', 'items_updated', 'error_message', 'metadata',
])]
final class PriceSourceSyncLog extends Model
{
    /** @use HasFactory<PriceSourceSyncLogFactory> */
    use HasFactory;

    protected static function newFactory(): PriceSourceSyncLogFactory
    {
        return PriceSourceSyncLogFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => PriceSourceSyncStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'items_fetched' => 'integer',
            'items_normalized' => 'integer',
            'items_matched' => 'integer',
            'items_updated' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<PriceSource, $this> */
    public function priceSource(): BelongsTo
    {
        return $this->belongsTo(PriceSource::class);
    }
}
