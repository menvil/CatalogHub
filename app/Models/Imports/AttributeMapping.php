<?php

namespace App\Models\Imports;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $import_source_id
 * @property int $category_id
 * @property string $raw_key
 * @property string $normalized_raw_key
 * @property int|null $attribute_definition_id
 * @property string $confidence
 * @property string $status
 */
#[Fillable([
    'import_source_id',
    'category_id',
    'raw_key',
    'normalized_raw_key',
    'attribute_definition_id',
    'confidence',
    'status',
    'mapping_type',
    'notes',
])]
final class AttributeMapping extends Model
{
    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<ImportSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class, 'import_source_id');
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
}
