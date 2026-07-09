<?php

namespace App\Models;

use Database\Factories\MarketUnitPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'market_code',
    'dimension_id',
    'preferred_unit_id',
])]
final class MarketUnitPreference extends Model
{
    /** @use HasFactory<MarketUnitPreferenceFactory> */
    use HasFactory;

    protected static function newFactory(): MarketUnitPreferenceFactory
    {
        return MarketUnitPreferenceFactory::new();
    }

    /**
     * @return BelongsTo<MeasurementDimension, $this>
     */
    public function dimension(): BelongsTo
    {
        return $this->belongsTo(MeasurementDimension::class, 'dimension_id');
    }

    /**
     * @return BelongsTo<MeasurementUnit, $this>
     */
    public function preferredUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'preferred_unit_id');
    }
}
