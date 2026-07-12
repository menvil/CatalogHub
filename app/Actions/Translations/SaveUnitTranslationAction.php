<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\Translations\UnitTranslation;
use App\Services\Translations\TranslationSourceHashService;
use App\Services\Translations\TranslationStatsService;
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

        $translation = UnitTranslation::query()->updateOrCreate(
            ['measurement_unit_id' => $unit->id, 'locale' => $locale->code],
            [
                'locale_id' => $locale->id,
                'short_name' => $data['short_name'] ?? null,
                'long_name' => $data['long_name'] ?? null,
                'plural_name' => $data['plural_name'] ?? null,
                'symbol_position' => $symbolPosition,
                'space_between_value_and_unit' => (bool) ($data['space_between_value_and_unit'] ?? true),
                'status' => $data['status'] ?? TranslationStatus::HumanReviewed,
            ],
        );

        $translation->forceFill(['source_hash' => $this->hashService->forUnit($unit)])->save();
        TranslationStatsService::forgetDashboardCache();

        return $translation;
    }
}
