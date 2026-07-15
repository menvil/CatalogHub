<?php

namespace App\Models;

use App\Enums\PriceSourceStatus;
use App\Enums\PriceSourceType;
use App\Enums\PriceSourceUpdateFrequency;
use Carbon\CarbonInterface;
use Database\Factories\PriceSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property PriceSourceType $type
 * @property PriceSourceStatus $status
 * @property PriceSourceUpdateFrequency|null $update_frequency
 * @property array<string, mixed>|null $config_json
 * @property CarbonInterface|null $last_sync_at
 */
#[Fillable([
    'market_id', 'code', 'name', 'type', 'status', 'config_json',
    'update_frequency', 'last_sync_at',
])]
final class PriceSource extends Model
{
    /** @use HasFactory<PriceSourceFactory> */
    use HasFactory;

    protected static function newFactory(): PriceSourceFactory
    {
        return PriceSourceFactory::new();
    }

    protected function casts(): array
    {
        return [
            'type' => PriceSourceType::class,
            'status' => PriceSourceStatus::class,
            'update_frequency' => PriceSourceUpdateFrequency::class,
            'config_json' => 'array',
            'last_sync_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Market, $this> */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /** @return HasOne<PriceSourceCredential, $this> */
    public function credentials(): HasOne
    {
        return $this->hasOne(PriceSourceCredential::class);
    }

    /** @return HasMany<PriceSourceSyncLog, $this> */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(PriceSourceSyncLog::class);
    }

    /** @return HasMany<RawPriceOffer, $this> */
    public function rawOffers(): HasMany
    {
        return $this->hasMany(RawPriceOffer::class);
    }

    /** @return HasMany<ExternalProductMapping, $this> */
    public function mappings(): HasMany
    {
        return $this->hasMany(ExternalProductMapping::class);
    }

    /** @return HasMany<MarketOffer, $this> */
    public function offers(): HasMany
    {
        return $this->hasMany(MarketOffer::class);
    }
}
