<?php

namespace App\Models\CentralCatalog;

use Database\Factories\CentralProductAttributeValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

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

    protected static function booted(): void
    {
        self::saving(function (self $value): void {
            $value->normalizeTypedColumns();
            $value->validateConfidence();
        });
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

    private function normalizeTypedColumns(): void
    {
        match ($this->value_type) {
            'integer', 'decimal' => $this->fill([
                'value_text' => null,
                'value_bool' => null,
                'value_enum_code' => null,
                'value_json' => null,
            ]),
            'string', 'text' => $this->fill([
                'value_number' => null,
                'value_bool' => null,
                'value_enum_code' => null,
                'value_json' => null,
                'value_min' => null,
                'value_max' => null,
                'source_unit' => null,
                'canonical_value' => null,
                'canonical_unit' => null,
            ]),
            'boolean' => $this->fill([
                'value_text' => null,
                'value_number' => null,
                'value_enum_code' => null,
                'value_json' => null,
                'value_min' => null,
                'value_max' => null,
                'source_unit' => null,
                'canonical_value' => null,
                'canonical_unit' => null,
            ]),
            'enum' => $this->fill([
                'value_text' => null,
                'value_number' => null,
                'value_bool' => null,
                'value_json' => null,
                'value_min' => null,
                'value_max' => null,
                'source_unit' => null,
                'canonical_value' => null,
                'canonical_unit' => null,
            ]),
            'multi_enum', 'json' => $this->fill([
                'value_text' => null,
                'value_number' => null,
                'value_bool' => null,
                'value_enum_code' => null,
                'value_min' => null,
                'value_max' => null,
                'source_unit' => null,
                'canonical_value' => null,
                'canonical_unit' => null,
            ]),
            default => null,
        };
    }

    private function validateConfidence(): void
    {
        if ($this->confidence === null) {
            return;
        }

        $confidence = (float) $this->confidence;

        if ($confidence < 0 || $confidence > 1) {
            throw new InvalidArgumentException('Attribute value confidence must be between 0 and 1.');
        }
    }
}
