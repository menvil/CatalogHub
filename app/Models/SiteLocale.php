<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['site_id', 'locale_code', 'is_default', 'is_enabled', 'position'])]
final class SiteLocale extends Model
{
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<Locale, $this> */
    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'locale_code', 'code');
    }

    /** @param Builder<SiteLocale> $query */
    public function scopeEnabled(Builder $query): void
    {
        $query->where('is_enabled', true);
    }

    /** @param Builder<SiteLocale> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
