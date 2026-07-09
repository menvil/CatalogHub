<?php

namespace App\Models\CentralCatalog;

use Database\Factories\CentralProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'model', 'slug'])]
final class CentralProduct extends Model
{
    /** @use HasFactory<CentralProductFactory> */
    use HasFactory;

    protected $table = 'central_products';

    protected static function newFactory(): CentralProductFactory
    {
        return CentralProductFactory::new();
    }
}
