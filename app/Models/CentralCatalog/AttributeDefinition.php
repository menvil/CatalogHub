<?php

namespace App\Models\CentralCatalog;

use App\Enums\AttributeDataType;
use Database\Factories\AttributeDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property AttributeDataType $data_type
 * @property int $options_count
 */
#[Fillable([
    'central_category_id',
    'attribute_section_id',
    'code',
    'name',
    'data_type',
    'dimension',
    'canonical_unit',
    'position',
    'is_required',
    'is_filterable',
    'is_sortable',
    'is_comparable',
    'is_visible',
    'is_searchable',
])]
final class AttributeDefinition extends Model
{
    /** @use HasFactory<AttributeDefinitionFactory> */
    use HasFactory;

    protected $table = 'attribute_definitions';

    protected static function newFactory(): AttributeDefinitionFactory
    {
        return AttributeDefinitionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'data_type' => AttributeDataType::class,
            'position' => 'integer',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_sortable' => 'boolean',
            'is_comparable' => 'boolean',
            'is_visible' => 'boolean',
            'is_searchable' => 'boolean',
        ];
    }

    /**
     * @param  Builder<AttributeDefinition>  $query
     * @return Builder<AttributeDefinition>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy($this->getKeyName());
    }

    /**
     * @param  Builder<AttributeDefinition>  $query
     * @return Builder<AttributeDefinition>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * @param  Builder<AttributeDefinition>  $query
     * @return Builder<AttributeDefinition>
     */
    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('is_searchable', true);
    }

    /**
     * @param  Builder<AttributeDefinition>  $query
     * @return Builder<AttributeDefinition>
     */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    /**
     * @param  Builder<AttributeDefinition>  $query
     * @return Builder<AttributeDefinition>
     */
    public function scopeFilterable(Builder $query): Builder
    {
        return $query->where('is_filterable', true);
    }

    /**
     * @param  Builder<AttributeDefinition>  $query
     * @return Builder<AttributeDefinition>
     */
    public function scopeSortable(Builder $query): Builder
    {
        return $query->where('is_sortable', true);
    }

    /**
     * @param  Builder<AttributeDefinition>  $query
     * @return Builder<AttributeDefinition>
     */
    public function scopeComparable(Builder $query): Builder
    {
        return $query->where('is_comparable', true);
    }

    /**
     * @return BelongsTo<CentralCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CentralCategory::class, 'central_category_id');
    }

    /**
     * @return BelongsTo<AttributeSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(AttributeSection::class, 'attribute_section_id');
    }

    /**
     * @return HasMany<AttributeOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class, 'attribute_definition_id');
    }
}
