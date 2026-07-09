<?php

namespace App\Models\CentralCatalog;

use App\Enums\CentralProductStatus;
use Database\Factories\CentralProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['central_brand_id', 'name', 'model', 'slug', 'status'])]
/**
 * @property CentralProductStatus $status
 */
final class CentralProduct extends Model
{
    /** @use HasFactory<CentralProductFactory> */
    use HasFactory;

    protected $table = 'central_products';

    protected static function newFactory(): CentralProductFactory
    {
        return CentralProductFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => CentralProductStatus::class,
        ];
    }

    /**
     * @return BelongsTo<CentralBrand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(CentralBrand::class, 'central_brand_id');
    }
}
