<?php

namespace App\Models\CentralCatalog;

use Database\Factories\CentralCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'position'])]
final class CentralCategory extends Model
{
    /** @use HasFactory<CentralCategoryFactory> */
    use HasFactory;

    protected $table = 'central_categories';

    protected static function newFactory(): CentralCategoryFactory
    {
        return CentralCategoryFactory::new();
    }
}
