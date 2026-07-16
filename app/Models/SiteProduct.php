<?php

namespace App\Models;

use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['site_id', 'central_product_id', 'visibility', 'is_featured', 'position', 'published_version', 'settings_json'])]
final class SiteProduct extends Model
{
    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'position' => 'integer', 'settings_json' => 'array'];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<CentralProduct, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class, 'central_product_id');
    }

    /** @param Builder<SiteProduct> $query */
    public function scopeVisible(Builder $query): void
    {
        $query->where('visibility', 'visible');
    }
}
