<?php

namespace App\Models;

use App\Exceptions\Units\CannotConvertUnitException;
use Database\Factories\MeasurementUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'dimension_id',
    'code',
    'symbol',
    'name',
    'system',
    'factor_to_canonical',
    'offset_to_canonical',
    'precision_default',
    'aliases_json',
    'is_canonical',
    'is_active',
])]
final class MeasurementUnit extends Model
{
    /** @use HasFactory<MeasurementUnitFactory> */
    use HasFactory;

    protected static function newFactory(): MeasurementUnitFactory
    {
        return MeasurementUnitFactory::new();
    }

    protected function casts(): array
    {
        return [
            'factor_to_canonical' => 'decimal:10',
            'offset_to_canonical' => 'decimal:10',
            'precision_default' => 'integer',
            'aliases_json' => 'array',
            'is_canonical' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<MeasurementUnit>  $query
     * @return Builder<MeasurementUnit>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function toCanonical(float|string $value): float
    {
        return ((float) $value * (float) $this->factor_to_canonical) + (float) $this->offset_to_canonical;
    }

    public function fromCanonical(float|string $canonicalValue): float
    {
        $factor = (float) $this->factor_to_canonical;

        if ($factor === 0.0) {
            throw CannotConvertUnitException::invalidFactor($this->code);
        }

        return ((float) $canonicalValue - (float) $this->offset_to_canonical) / $factor;
    }

    /**
     * @return BelongsTo<MeasurementDimension, $this>
     */
    public function dimension(): BelongsTo
    {
        return $this->belongsTo(MeasurementDimension::class, 'dimension_id');
    }
}
