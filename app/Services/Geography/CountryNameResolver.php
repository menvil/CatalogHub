<?php

declare(strict_types=1);

namespace App\Services\Geography;

use App\Models\Geography\Country;
use App\Models\Geography\CountryTranslation;

final class CountryNameResolver
{
    public function nameFor(Country $country, string $locale): string
    {
        $locale = str_replace('_', '-', trim($locale));
        $base = strtolower(explode('-', $locale)[0]);
        $candidates = array_values(array_unique(array_filter([$locale, $base])));

        $translations = $country->relationLoaded('translations')
            ? $country->translations
            : $country->translations()->whereIn('locale', $candidates)->get();

        foreach ($candidates as $candidate) {
            $translation = $translations->first(
                static fn (CountryTranslation $translation): bool => $translation->locale === $candidate,
            );

            if ($translation !== null) {
                return $translation->name;
            }
        }

        return $country->canonical_name;
    }
}
