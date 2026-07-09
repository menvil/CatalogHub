<?php

namespace App\Models\CentralCatalog;

use Database\Factories\AttributeOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * @return BelongsTo<AttributeDefinition, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }
}
