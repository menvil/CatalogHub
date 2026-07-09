<?php

namespace App\Models\CentralCatalog;

use App\Enums\CentralProductVariantStatus;
use Database\Factories\CentralProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['central_product_id', 'name', 'sku', 'status', 'position'])]
/**
 * @property CentralProductVariantStatus $status
 */
final class CentralProductVariant extends Model
{
    /** @use HasFactory<CentralProductVariantFactory> */
    use HasFactory;

    protected $table = 'central_product_variants';

    protected static function newFactory(): CentralProductVariantFactory
    {
        return CentralProductVariantFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => CentralProductVariantStatus::class,
        ];
    }

    /**
     * @return BelongsTo<CentralProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class, 'central_product_id');
    }
}
