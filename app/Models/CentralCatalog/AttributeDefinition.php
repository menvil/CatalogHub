<?php

namespace App\Models\CentralCatalog;

use App\Enums\AttributeDataType;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use App\Models\Translations\AttributeTranslation;
use Database\Factories\AttributeDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property AttributeDataType $data_type
 * @property MeasurementDimension|null $measurementDimension
 * @property MeasurementUnit|null $canonicalMeasurementUnit
 * @property int $options_count
 */
#[Fillable([
    'measurement_dimension_id',
    'canonical_measurement_unit_id',
    'code',
    'name',
    'data_type',
])]
final class AttributeDefinition extends Model
{
    /** @use HasFactory<AttributeDefinitionFactory> */
    use HasFactory;

    // PostgreSQL stores Laravel unsignedInteger as signed INTEGER.
    public const MAX_POSITION = 2147483647;

    protected $table = 'attribute_definitions';

    protected static function newFactory(): AttributeDefinitionFactory
    {
        return AttributeDefinitionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'data_type' => AttributeDataType::class,
        ];
    }

    /** @return HasMany<CategoryAttributeAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(CategoryAttributeAssignment::class, 'attribute_definition_id');
    }

    /** @return BelongsTo<MeasurementDimension, $this> */
    public function measurementDimension(): BelongsTo
    {
        return $this->belongsTo(MeasurementDimension::class, 'measurement_dimension_id');
    }

    /** @return BelongsTo<MeasurementUnit, $this> */
    public function canonicalMeasurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'canonical_measurement_unit_id');
    }

    /**
     * @return HasMany<AttributeOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class, 'attribute_definition_id');
    }

    /**
     * @return HasMany<CentralProductAttributeValue, $this>
     */
    public function productValues(): HasMany
    {
        return $this->hasMany(CentralProductAttributeValue::class, 'attribute_definition_id');
    }

    /**
     * @return HasMany<AttributeTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(AttributeTranslation::class, 'attribute_definition_id');
    }
}
