<?php

declare(strict_types=1);

namespace App\Models\Imports;

use App\Models\CentralCatalog\CentralBrand;
use Database\Factories\CentralBrandExternalIdentityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'central_brand_id',
    'import_source_id',
    'external_id',
    'external_id_hash',
    'external_url',
])]
final class CentralBrandExternalIdentity extends Model
{
    /** @use HasFactory<CentralBrandExternalIdentityFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $hidden = ['external_id_hash'];

    protected static function newFactory(): CentralBrandExternalIdentityFactory
    {
        return CentralBrandExternalIdentityFactory::new();
    }

    /** @return BelongsTo<CentralBrand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(CentralBrand::class, 'central_brand_id');
    }

    /** @return BelongsTo<ImportSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class, 'import_source_id');
    }
}
