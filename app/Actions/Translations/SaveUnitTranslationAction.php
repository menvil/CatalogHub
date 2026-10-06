<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\Translations\UnitTranslation;
use App\Services\Translations\TranslationLocaleIdentity;
use App\Services\Translations\TranslationSourceHashService;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class SaveUnitTranslationAction
{
    public function __construct(private TranslationSourceHashService $hashService) {}

    /** @param array<string, mixed> $data */
    public function handle(MeasurementUnit $unit, Locale $locale, array $data): UnitTranslation
    {
        $symbolPosition = (string) ($data['symbol_position'] ?? 'after');

        if (! in_array($symbolPosition, ['before', 'after'], true)) {
            throw new InvalidArgumentException("Invalid unit symbol position [{$symbolPosition}].");
        }

        $translation = DB::transaction(function () use ($unit, $locale, $data, $symbolPosition): UnitTranslation {
            $unit = app(TranslationLocaleIdentity::class)->lockOwner($unit);
            $locale = Locale::query()->lockForUpdate()->findOrFail($locale->id);

            $translation = UnitTranslation::query()->updateOrCreate(
                ['measurement_unit_id' => $unit->id, 'locale_id' => $locale->id],
                [
                    'locale' => $locale->code,
                    'short_name' => $data['short_name'] ?? null,
                    'long_name' => $data['long_name'] ?? null,
                    'plural_name' => $data['plural_name'] ?? null,
                    'symbol_position' => $symbolPosition,
                    'space_between_value_and_unit' => (bool) ($data['space_between_value_and_unit'] ?? true),
                    'status' => $data['status'] ?? TranslationStatus::HumanReviewed,
                ],
            );

            $translation->forceFill(['source_hash' => $this->hashService->forUnit($unit)])->save();

            return $translation;
        }, 3);

        TranslationStatsService::forgetDashboardCache();

        return $translation;
    }
}
