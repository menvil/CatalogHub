<?php

namespace App\Models\Translations;

use App\Enums\TranslationStatus;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\User;
use Database\Factories\UnitTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['measurement_unit_id', 'locale_id', 'locale', 'short_name', 'long_name', 'plural_name', 'symbol_position', 'space_between_value_and_unit', 'status'])]
final class UnitTranslation extends Model
{
    /** @use HasFactory<UnitTranslationFactory> */
    use HasFactory;

    protected static function newFactory(): UnitTranslationFactory
    {
        return UnitTranslationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'space_between_value_and_unit' => 'boolean',
            'status' => TranslationStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<MeasurementUnit, $this> */
    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id');
    }

    /** @return BelongsTo<Locale, $this> */
    public function localeModel(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'locale_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
