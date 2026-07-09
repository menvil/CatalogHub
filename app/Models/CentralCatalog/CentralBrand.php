<?php

namespace App\Models\CentralCatalog;

use App\Enums\CentralBrandStatus;
use Database\Factories\CentralBrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'status'])]
/**
 * @property CentralBrandStatus $status
 */
final class CentralBrand extends Model
{
    /** @use HasFactory<CentralBrandFactory> */
    use HasFactory;

    protected $table = 'central_brands';

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
}
