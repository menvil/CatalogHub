<?php

namespace App\Models\CentralCatalog;

use Database\Factories\CategoryComparisonAttributeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['central_category_id', 'category_attribute_assignment_id', 'position', 'is_visible'])]
final class CategoryComparisonAttribute extends Model
{
    /** @use HasFactory<CategoryComparisonAttributeFactory> */
    use HasFactory;

    protected static function newFactory(): CategoryComparisonAttributeFactory
    {
        return CategoryComparisonAttributeFactory::new();
    }

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_visible' => 'boolean'];
    }

    /** @return BelongsTo<CategoryAttributeAssignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(CategoryAttributeAssignment::class, 'category_attribute_assignment_id');
    }

    /** @return BelongsTo<CentralCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CentralCategory::class, 'central_category_id');
    }
}
