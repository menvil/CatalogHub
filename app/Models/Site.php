<?php

namespace App\Models;

use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property SiteMode $mode
 * @property SiteStatus $status
 * @property int|null $theme_id
 * @property array<string, mixed>|null $settings_json
 * @property-read Theme|null $theme
 */
#[Fillable(['market_id', 'theme_id', 'code', 'name', 'domain', 'mode', 'default_locale', 'status', 'settings_json'])]
final class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): SiteFactory
    {
        return SiteFactory::new();
    }

    protected function casts(): array
    {
        return ['mode' => SiteMode::class, 'status' => SiteStatus::class, 'settings_json' => 'array'];
    }

    /** @return BelongsTo<Market, $this> */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /** @return BelongsTo<Theme, $this> */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /** @return HasMany<SiteFeature, $this> */
    public function features(): HasMany
    {
        return $this->hasMany(SiteFeature::class);
    }

    /** @return HasMany<SiteProduct, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(SiteProduct::class);
    }

    /** @return HasMany<SiteOverride, $this> */
    public function overrides(): HasMany
    {
        return $this->hasMany(SiteOverride::class);
    }

    /** @return HasMany<SiteHomeBlock, $this> */
    public function homeBlocks(): HasMany
    {
        return $this->hasMany(SiteHomeBlock::class);
    }

    /** @return HasMany<SiteFacetOverride, $this> */
    public function facetOverrides(): HasMany
    {
        return $this->hasMany(SiteFacetOverride::class);
    }

    public function isSingleCategory(): bool
    {
        return $this->mode === SiteMode::SingleCategory;
    }

    public function isMultiCategory(): bool
    {
        return $this->mode === SiteMode::MultiCategory;
    }

    public function isActive(): bool
    {
        return $this->status === SiteStatus::Active;
    }
}
