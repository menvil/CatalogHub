<?php

namespace App\Models\CentralCatalog;

use Database\Factories\CentralProductAttributeValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'central_product_id',
    'attribute_definition_id',
    'raw_value',
    'value_type',
    'value_text',
    'value_number',
    'value_bool',
    'value_enum_code',
    'value_json',
    'value_min',
    'value_max',
    'source_unit',
    'canonical_value',
    'canonical_unit',
    'confidence',
    'source_type',
    'source_id',
    'source_reference',
])]
final class CentralProductAttributeValue extends Model
{
    /** @use HasFactory<CentralProductAttributeValueFactory> */
    use HasFactory;

    protected $table = 'central_product_attribute_values';

    protected static function newFactory(): CentralProductAttributeValueFactory
    {
        return CentralProductAttributeValueFactory::new();
    }

    protected function casts(): array
    {
        return [
            'value_bool' => 'boolean',
            'value_json' => 'array',
            'source_reference' => 'array',
            'value_number' => 'decimal:6',
            'value_min' => 'decimal:6',
            'value_max' => 'decimal:6',
            'canonical_value' => 'decimal:6',
            'confidence' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<CentralProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class, 'central_product_id');
    }

    /**
     * @return BelongsTo<AttributeDefinition, $this>
     */
    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }
}
