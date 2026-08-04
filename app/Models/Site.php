<?php

namespace App\Models;

use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property SiteMode $mode
 * @property SiteStatus $status
 * @property int|null $theme_id
 * @property array<string, mixed>|null $settings_json
 * @property-read Theme|null $theme
 */
#[Fillable(['market_id', 'theme_id', 'code', 'name', 'domain', 'mode', 'default_locale', 'currency_code', 'timezone', 'status', 'settings_json'])]
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

    /** @return HasMany<SiteLocale, $this> */
    public function locales(): HasMany
    {
        return $this->hasMany(SiteLocale::class);
    }

    /** @return HasOne<SiteLocale, $this> */
    public function defaultLocale(): HasOne
    {
        return $this->hasOne(SiteLocale::class)->where('is_default', true);
    }

    /** @return HasMany<SiteDomain, $this> */
    public function domains(): HasMany
    {
        return $this->hasMany(SiteDomain::class);
    }

    /** @return HasOne<SiteDomain, $this> */
    public function primaryDomain(): HasOne
    {
        return $this->hasOne(SiteDomain::class)
            ->where('is_primary', true)
            ->where('is_active', true);
    }

    /** @return HasMany<SiteCategory, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(SiteCategory::class);
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

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<SiteMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(SiteMembership::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'site_user_memberships')
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    /** @return HasMany<Lead, $this> */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /** @return HasMany<ContentItem, $this> */
    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentItem::class);
    }

    /** @return BelongsToMany<PriceSource, $this, SitePriceSource, 'pivot'> */
    public function priceSources(): BelongsToMany
    {
        return $this->belongsToMany(PriceSource::class, 'site_price_sources')
            ->using(SitePriceSource::class)
            ->withPivot(['enabled', 'priority', 'config_json'])
            ->withTimestamps();
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
        return $this->status->isPubliclyAvailable();
    }

    /** @param Builder<Site> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', SiteStatus::Active);
    }

    /** @param Builder<Site> $query */
    public function scopeAdministrable(Builder $query): void
    {
        $query->whereIn('status', array_map(
            static fn (SiteStatus $status): string => $status->value,
            array_filter(SiteStatus::cases(), static fn (SiteStatus $status): bool => $status->allowsAdministration()),
        ));
    }

    /** @param Builder<Site> $query */
    public function scopeByCode(Builder $query, string $code): void
    {
        $query->where('code', $code);
    }
}
