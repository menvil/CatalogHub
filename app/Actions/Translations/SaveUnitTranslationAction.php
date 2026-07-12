<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\Translations\UnitTranslation;
use App\Services\Translations\TranslationSourceHashService;

final readonly class SaveUnitTranslationAction
{
    public function __construct(private TranslationSourceHashService $hashService) {}

    /** @param array<string, mixed> $data */
    public function handle(MeasurementUnit $unit, Locale $locale, array $data): UnitTranslation
    {
        return UnitTranslation::query()->updateOrCreate(
            ['measurement_unit_id' => $unit->id, 'locale' => $locale->code],
            [
                'locale_id' => $locale->id,
                'short_name' => $data['short_name'] ?? null,
                'long_name' => $data['long_name'] ?? null,
                'plural_name' => $data['plural_name'] ?? null,
                'symbol_position' => $data['symbol_position'] ?? 'after',
                'space_between_value_and_unit' => (bool) ($data['space_between_value_and_unit'] ?? true),
                'status' => $data['status'] ?? TranslationStatus::HumanReviewed,
                'source_hash' => $this->hashService->forUnit($unit),
            ],
        );
    }
}
