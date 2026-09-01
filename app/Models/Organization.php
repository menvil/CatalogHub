<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property string $name
 * @property string $normalized_name
 * @property string $normalized_name_prefix
 */
#[Fillable(['name'])]
final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $hidden = ['normalized_name', 'normalized_name_prefix'];

    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }

    /** @return HasMany<CentralBrandOwnership, $this> */
    public function brandOwnerships(): HasMany
    {
        return $this->hasMany(CentralBrandOwnership::class);
    }

    /** @return HasManyThrough<CentralBrand, CentralBrandOwnership, $this> */
    public function ownedBrands(): HasManyThrough
    {
        return $this->hasManyThrough(
            CentralBrand::class,
            CentralBrandOwnership::class,
            'organization_id',
            'id',
            'id',
            'central_brand_id',
        );
    }
}
