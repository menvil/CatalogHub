<?php

namespace App\Services\Translations;

use App\Enums\TranslationStatus;
use App\Models\Locale;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\Translations\UnitTranslation;

final readonly class TranslationStatsService
{
    public function __construct(private TranslationCompletenessService $completeness) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $coverageByLocale = $this->completeness->allActiveLocales();

        return [
            'locales_count' => Locale::query()->count(),
            'approved_count' => $this->countByStatus(TranslationStatus::Approved),
            'outdated_count' => $this->countByStatus(TranslationStatus::Outdated),
            'missing_count' => array_sum(array_map(
                fn (array $localeStats): int => (int) $localeStats['missing'],
                $coverageByLocale,
            )),
            'coverage_by_locale' => $coverageByLocale,
        ];
    }

    private function countByStatus(TranslationStatus $status): int
    {
        return array_sum(array_map(
            fn (string $model): int => $model::query()->where('status', $status)->count(),
            $this->translationModels(),
        ));
    }

    /**
     * @return list<class-string>
     */
    private function translationModels(): array
    {
        return [
            ProductTranslation::class,
            CategoryTranslation::class,
            AttributeTranslation::class,
            AttributeSectionTranslation::class,
            AttributeOptionTranslation::class,
            UnitTranslation::class,
        ];
    }
}
