<?php

namespace App\Services\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\Translations\UnitTranslation;

final class TranslationCompletenessService
{
    /**
     * @return array<string, mixed>
     */
    public function forLocale(string $locale): array
    {
        $byEntity = [];
        $totalRequired = 0;
        $totalApproved = 0;
        $totalMissing = 0;
        $totalOutdated = 0;

        foreach ($this->configs() as $config) {
            $required = $config['source']::query()->count();
            $statusCounts = $config['translation']::query()
                ->selectRaw('status, count(*) as aggregate')
                ->where('locale', $locale)
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $approved = (int) ($statusCounts[TranslationStatus::Approved->value] ?? 0);
            $outdated = (int) ($statusCounts[TranslationStatus::Outdated->value] ?? 0);
            $existing = (int) $statusCounts->sum();
            $missing = max(0, $required - $existing);
            $coverage = $required === 0 ? 100.0 : round(($approved / $required) * 100, 1);

            $byEntity[$config['key']] = [
                'required' => $required,
                'approved' => $approved,
                'missing' => $missing,
                'outdated' => $outdated,
                'coverage' => $coverage,
            ];

            $totalRequired += $required;
            $totalApproved += $approved;
            $totalMissing += $missing;
            $totalOutdated += $outdated;
        }

        return [
            'locale' => $locale,
            'coverage' => $totalRequired === 0 ? 100.0 : round(($totalApproved / $totalRequired) * 100, 1),
            'approved' => $totalApproved,
            'missing' => $totalMissing,
            'outdated' => $totalOutdated,
            'required' => $totalRequired,
            'by_entity' => $byEntity,
            'categoryCoverage' => $byEntity['categories']['coverage'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allActiveLocales(): array
    {
        return Locale::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->orderBy('code')
            ->pluck('code')
            ->map(fn (string $code): array => $this->forLocale($code))
            ->all();
    }

    /**
     * @return list<array{key: string, source: class-string, translation: class-string}>
     */
    private function configs(): array
    {
        return [
            ['key' => 'products', 'source' => CentralProduct::class, 'translation' => ProductTranslation::class],
            ['key' => 'categories', 'source' => CentralCategory::class, 'translation' => CategoryTranslation::class],
            ['key' => 'attributes', 'source' => AttributeDefinition::class, 'translation' => AttributeTranslation::class],
            ['key' => 'sections', 'source' => AttributeSection::class, 'translation' => AttributeSectionTranslation::class],
            ['key' => 'options', 'source' => AttributeOption::class, 'translation' => AttributeOptionTranslation::class],
            ['key' => 'units', 'source' => MeasurementUnit::class, 'translation' => UnitTranslation::class],
        ];
    }
}
