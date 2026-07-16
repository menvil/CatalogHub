<?php

namespace App\Models;

use App\Models\CentralCatalog\CentralProduct;
use Database\Factories\ProductVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'central_product_id',
    'version',
    'changed_by_user_id',
    'change_type',
    'reason',
    'snapshot_json',
    'diff_json',
    'metadata_json',
])]
final class ProductVersion extends Model
{
    /** @use HasFactory<ProductVersionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'central_product_versions';

    protected static function newFactory(): ProductVersionFactory
    {
        return ProductVersionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot_json' => 'array',
            'diff_json' => 'array',
            'metadata_json' => 'array',
        ];
    }

    /** @return BelongsTo<CentralProduct, $this> */
    public function centralProduct(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class);
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
