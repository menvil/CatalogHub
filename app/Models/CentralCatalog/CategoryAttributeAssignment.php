<?php

namespace App\Models\CentralCatalog;

use Database\Factories\CategoryAttributeAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['central_category_id', 'attribute_definition_id', 'attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable'])]
final class CategoryAttributeAssignment extends Model
{
    /** @use HasFactory<CategoryAttributeAssignmentFactory> */
    use HasFactory;

    protected static function newFactory(): CategoryAttributeAssignmentFactory
    {
        return CategoryAttributeAssignmentFactory::new();
    }

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_required' => 'boolean', 'is_visible' => 'boolean', 'is_searchable' => 'boolean', 'is_sortable' => 'boolean'];
    }

    /** @return BelongsTo<CentralCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CentralCategory::class, 'central_category_id');
    }

    /** @return BelongsTo<AttributeDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }

    /** @return BelongsTo<AttributeSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(AttributeSection::class, 'attribute_section_id');
    }
}
