<?php

namespace App\Models\CentralCatalog;

use Database\Factories\CatalogTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
final class CatalogTag extends Model
{
    /** @use HasFactory<CatalogTagFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $hidden = ['normalized_name', 'normalized_name_hash'];

    protected static function newFactory(): CatalogTagFactory
    {
        return CatalogTagFactory::new();
    }

    /** @return BelongsToMany<CentralBrand, $this> */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(CentralBrand::class, 'central_brand_tag');
    }
}
