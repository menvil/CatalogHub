<?php

declare(strict_types=1);

namespace App\Models\Geography;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $alpha2
 * @property string $alpha3
 * @property string $numeric_code
 * @property string $canonical_name
 * @property string|null $region_code
 * @property string|null $subregion_code
 * @property string|null $intermediate_region_code
 * @property bool $is_active
 */
#[Fillable([
    'alpha2',
    'alpha3',
    'numeric_code',
    'canonical_name',
    'region_code',
    'subregion_code',
    'intermediate_region_code',
    'is_active',
])]
final class Country extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @param Builder<Country> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return HasMany<CountryTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(CountryTranslation::class);
    }
}
