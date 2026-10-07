<?php

namespace App\Models\CentralCatalog;

use Database\Factories\CategoryAttributeAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property AttributeSection|null $section */
#[Fillable(['central_category_id', 'attribute_definition_id', 'attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable'])]
final class CategoryAttributeAssignment extends Model
{
    /** @use HasFactory<CategoryAttributeAssignmentFactory> */
    use HasFactory;

    protected static function newFactory(): CategoryAttributeAssignmentFactory
    {
        return CategoryAttributeAssignmentFactory::new();
    }

    /** @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /** @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /** @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('is_searchable', true);
    }

    /** @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    /** @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeSortable(Builder $query): Builder
    {
        return $query->where('is_sortable', true);
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
