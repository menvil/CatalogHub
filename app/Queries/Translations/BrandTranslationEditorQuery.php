<?php

declare(strict_types=1);

namespace App\Queries\Translations;

use App\Data\Translations\BrandTranslationEditorData;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Services\Translations\TranslationSourceHashService;

final readonly class BrandTranslationEditorQuery
{
    public function __construct(
        private TranslationSourceHashService $sourceHashes,
        private BrandTranslationActivityQuery $activity,
    ) {}

    public function forBrand(CentralBrand $brand, ?Locale $selectedLocale = null): BrandTranslationEditorData
    {
        $locales = Locale::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->orderBy('code')
            ->get();

        $translations = BrandTranslation::query()
            ->where('brand_id', $brand->getKey())
            ->whereIn('locale_id', $locales->modelKeys())
            ->with('approvedBy')
            ->get()
            ->keyBy(fn (BrandTranslation $translation): int => (int) $translation->locale_id);

        $translation = $selectedLocale instanceof Locale
            ? $translations->get($selectedLocale->getKey())
            : null;
        $translation = $translation instanceof BrandTranslation ? $translation : null;
        $currentSourceHash = $this->sourceHashes->forBrand($brand);

        return new BrandTranslationEditorData(
            brand: $brand,
            locales: $locales,
            translationsByLocale: $translations,
            selectedLocale: $selectedLocale,
            translation: $translation,
            currentSourceHash: $currentSourceHash,
            sourceHashMatches: $translation instanceof BrandTranslation
                && is_string($translation->source_hash)
                && hash_equals($currentSourceHash, $translation->source_hash),
            activity: $selectedLocale instanceof Locale
                ? $this->activity->forBrandAndLocale($brand, $selectedLocale)
                : collect(),
        );
    }
}
