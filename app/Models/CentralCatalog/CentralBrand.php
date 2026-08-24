<?php

namespace App\Models\CentralCatalog;

use App\Enums\CentralBrandStatus;
use App\Models\Translations\BrandTranslation;
use Database\Factories\CentralBrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CentralBrandStatus $status
 * @property string $normalized_name
 * @property string $normalized_name_hash
 * @property string|null $website_url
 * @property string|null $country_code
 */
#[Fillable(['name', 'slug', 'status', 'website_url', 'country_code'])]
final class CentralBrand extends Model
{
    /** @use HasFactory<CentralBrandFactory> */
    use HasFactory;

    protected $table = 'central_brands';

    /** @var list<string> */
    protected $hidden = ['normalized_name', 'normalized_name_hash'];

    protected static function newFactory(): CentralBrandFactory
    {
        return CentralBrandFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => CentralBrandStatus::class,
        ];
    }

    /** @param Builder<CentralBrand> $query */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', CentralBrandStatus::Draft);
    }

    /** @param Builder<CentralBrand> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CentralBrandStatus::Active);
    }

    /** @param Builder<CentralBrand> $query */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', CentralBrandStatus::Archived);
    }

    /**
     * @return HasMany<CentralProduct, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(CentralProduct::class, 'central_brand_id');
    }

    /**
     * @return HasMany<BrandTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(BrandTranslation::class, 'brand_id');
    }
}
