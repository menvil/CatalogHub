<?php

namespace App\Models\CentralCatalog;

use Database\Factories\AttributeOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property AttributeDefinition $attribute
 */
#[Fillable([
    'attribute_definition_id',
    'code',
    'label',
    'position',
    'is_visible',
])]
final class AttributeOption extends Model
{
    /** @use HasFactory<AttributeOptionFactory> */
    use HasFactory;

    public const MAX_POSITION = 4294967295;

    protected $table = 'attribute_options';

    protected static function newFactory(): AttributeOptionFactory
    {
        return AttributeOptionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * @param  Builder<AttributeOption>  $query
     * @return Builder<AttributeOption>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy($this->getKeyName());
    }

    /**
     * @return BelongsTo<AttributeDefinition, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }
}
