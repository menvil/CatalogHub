<?php

namespace App\Models;

use Database\Factories\MeasurementDimensionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'description',
    'base_unit_code',
    'sort_order',
    'is_active',
])]
final class MeasurementDimension extends Model
{
    /** @use HasFactory<MeasurementDimensionFactory> */
    use HasFactory;

    protected static function newFactory(): MeasurementDimensionFactory
    {
        return MeasurementDimensionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<MeasurementDimension>  $query
     * @return Builder<MeasurementDimension>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<MeasurementDimension>  $query
     * @return Builder<MeasurementDimension>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return HasMany<MeasurementUnit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(MeasurementUnit::class, 'dimension_id');
    }
}
