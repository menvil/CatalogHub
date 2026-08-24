<?php

declare(strict_types=1);

namespace App\Queries\Translations;

use App\Data\Translations\BrandTranslationEditorData;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;

final class BrandTranslationEditorQuery
{
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

        return new BrandTranslationEditorData(
            brand: $brand,
            locales: $locales,
            translationsByLocale: $translations,
            selectedLocale: $selectedLocale,
            translation: $translation instanceof BrandTranslation ? $translation : null,
        );
    }
}
