<?php

namespace App\Models\CentralCatalog;

use App\Models\Translations\AttributeSectionTranslation;
use Database\Factories\AttributeSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int|null $parent_id
 */
#[Fillable([
    'central_category_id',
    'parent_id',
    'code',
    'name',
    'position',
    'display_style',
    'is_collapsible',
    'is_visible',
])]
final class AttributeSection extends Model
{
    /** @use HasFactory<AttributeSectionFactory> */
    use HasFactory;

    public const MAX_POSITION = 4294967295;

    public const DISPLAY_STYLES = ['table', 'list'];

    protected $table = 'attribute_sections';

    protected static function newFactory(): AttributeSectionFactory
    {
        return AttributeSectionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_collapsible' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * @param  Builder<AttributeSection>  $query
     * @return Builder<AttributeSection>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy($this->getKeyName());
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
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<AttributeSection, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<AttributeDefinition, $this>
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(AttributeDefinition::class, 'attribute_section_id');
    }

    /**
     * @return HasMany<AttributeSectionTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(AttributeSectionTranslation::class, 'attribute_section_id');
    }
}
