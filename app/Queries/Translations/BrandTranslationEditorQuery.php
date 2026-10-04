<?php

declare(strict_types=1);

namespace App\Queries\Translations;

use App\Data\Translations\BrandTranslationEditorData;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Services\Translations\TranslationSourceHashService;
use Illuminate\Validation\ValidationException;

final readonly class BrandTranslationEditorQuery
{
    public function __construct(
        private TranslationSourceHashService $sourceHashes,
        private BrandTranslationActivityQuery $activity,
    ) {}

    public function forBrand(CentralBrand $brand, ?Locale $selectedLocale = null, ?string $sourceCode = null): BrandTranslationEditorData
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
        $sourceRank = static fn (?BrandTranslation $row): int => match ($row?->getRawOriginal('status')) {
            TranslationStatus::Approved->value => 0,
            TranslationStatus::HumanReviewed->value => 1,
            TranslationStatus::MachineTranslated->value => 2,
            TranslationStatus::Outdated->value => 3,
            default => 4,
        };
        $sourceLocales = $locales
            ->reject(fn (Locale $locale): bool => $selectedLocale?->is($locale) === true)
            ->sortBy(fn (Locale $locale): int => $sourceRank($translations->get($locale->getKey())))
            ->values();
        $sourceLocale = $sourceCode !== null ? $sourceLocales->firstWhere('code', $sourceCode) : null;

        if ($sourceCode !== null && ! $sourceLocale instanceof Locale) {
            throw ValidationException::withMessages([
                'source' => 'Choose an active source language from the language menu.',
            ]);
        }

        if ($sourceCode === null) {
            $usableSources = $sourceLocales->filter(fn (Locale $locale): bool => $translations->has($locale->getKey())
                && $sourceRank($translations->get($locale->getKey())) < 4);
            $sourceLocale = $usableSources->firstWhere('is_default', true)
                ?? $usableSources->first()
                ?? $sourceLocales->first(fn (Locale $locale): bool => $translations->has($locale->getKey()));
        }

        $sourceTranslation = $sourceLocale instanceof Locale ? $translations->get($sourceLocale->getKey()) : null;
        $currentSourceHash = $this->sourceHashes->forBrand($brand);

        return new BrandTranslationEditorData(
            brand: $brand,
            locales: $locales,
            translationsByLocale: $translations,
            selectedLocale: $selectedLocale,
            translation: $translation,
            sourceLocale: $sourceLocale,
            sourceTranslation: $sourceTranslation instanceof BrandTranslation ? $sourceTranslation : null,
            sourceLocales: $sourceLocales,
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
