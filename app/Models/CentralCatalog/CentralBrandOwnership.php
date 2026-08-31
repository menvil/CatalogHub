<?php

declare(strict_types=1);

namespace App\Models\CentralCatalog;

use App\Models\Organization;
use Database\Factories\CentralBrandOwnershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $central_brand_id
 * @property int $organization_id
 */
#[Fillable(['central_brand_id', 'organization_id'])]
final class CentralBrandOwnership extends Model
{
    /** @use HasFactory<CentralBrandOwnershipFactory> */
    use HasFactory;

    protected static function newFactory(): CentralBrandOwnershipFactory
    {
        return CentralBrandOwnershipFactory::new();
    }

    /** @return BelongsTo<CentralBrand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(CentralBrand::class, 'central_brand_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
