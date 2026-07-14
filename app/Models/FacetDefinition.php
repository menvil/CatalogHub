<?php

namespace App\Models;

use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use Database\Factories\FacetDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'attribute_definition_id',
    'code',
    'label_override',
    'facet_type',
    'source_type',
    'is_active',
    'is_filterable',
    'is_visible',
    'is_collapsible',
    'default_collapsed',
    'position',
    'config_json',
])]
final class FacetDefinition extends Model
{
    /** @use HasFactory<FacetDefinitionFactory> */
    use HasFactory;

    protected static function newFactory(): FacetDefinitionFactory
    {
        return FacetDefinitionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'facet_type' => FacetType::class,
            'source_type' => FacetSourceType::class,
            'is_active' => 'boolean',
            'is_filterable' => 'boolean',
            'is_visible' => 'boolean',
            'is_collapsible' => 'boolean',
            'default_collapsed' => 'boolean',
            'position' => 'integer',
            'config_json' => 'array',
        ];
    }

    /** @return BelongsTo<CentralCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CentralCategory::class, 'category_id');
    }

    /** @return BelongsTo<AttributeDefinition, $this> */
    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class);
    }

    /** @return HasMany<FacetOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(FacetOption::class)->orderBy('position')->orderBy('id');
    }
}
