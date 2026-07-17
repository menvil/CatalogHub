<?php

namespace App\Models;

use App\Models\CentralCatalog\CentralProduct;
use Database\Factories\SiteProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $published_version
 * @property Carbon|null $last_synced_at
 * @property array<string, mixed>|null $settings_json
 */
#[Fillable([
    'site_id', 'central_product_id', 'visibility', 'is_featured', 'position', 'published_version',
    'last_synced_at', 'sync_status', 'settings_json',
])]
final class SiteProduct extends Model
{
    /** @use HasFactory<SiteProductFactory> */
    use HasFactory;

    protected static function newFactory(): SiteProductFactory
    {
        return SiteProductFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'position' => 'integer',
            'published_version' => 'integer',
            'last_synced_at' => 'datetime',
            'settings_json' => 'array',
        ];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<CentralProduct, $this> */
    public function product(): BelongsTo
    {
        return $this->centralProduct();
    }

    /** @return BelongsTo<CentralProduct, $this> */
    public function centralProduct(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class, 'central_product_id');
    }

    /** @param Builder<SiteProduct> $query */
    public function scopeVisible(Builder $query): void
    {
        $query->where('visibility', 'visible');
    }
}
