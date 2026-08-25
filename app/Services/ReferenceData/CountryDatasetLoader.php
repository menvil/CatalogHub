<?php

declare(strict_types=1);

namespace App\Services\ReferenceData;

use JsonException;
use RuntimeException;

final class CountryDatasetLoader
{
    /**
     * @return array{
     *   manifest: array<string, mixed>,
     *   countries: list<array{alpha2: string, alpha3: string, numeric_code: string, canonical_name: string, region_code: string|null, subregion_code: string|null, intermediate_region_code: string|null}>,
     *   translations: list<array{alpha2: string, locale: string, name: string}>
     * }
     */
    public function load(?string $directory = null, string $manifestFilename = 'manifest.json'): array
    {
        $directory ??= database_path('reference-data/countries');
        if (basename($manifestFilename) !== $manifestFilename) {
            throw new RuntimeException('Country manifest must name a local file.');
        }

        $manifest = $this->decode($directory.'/'.$manifestFilename);
        $countriesFile = $this->payloadPath($directory, $manifest, 'countries');
        $translationsFile = $this->payloadPath($directory, $manifest, 'translations');
        $countriesPayload = $this->decode($countriesFile);
        $translationsPayload = $this->decode($translationsFile);
        $countries = $countriesPayload['countries'] ?? null;
        $translations = $translationsPayload['translations'] ?? null;

        if (! is_array($countries) || ! array_is_list($countries)) {
            throw new RuntimeException('Country dataset must contain a countries list.');
        }

        if (! is_array($translations) || ! array_is_list($translations)) {
            throw new RuntimeException('Country translation dataset must contain a translations list.');
        }

        $this->validateManifest($manifest, $countries, $translations);
        $this->validateCountries($countries);
        $this->validateTranslations($translations, $countries, $manifest);
        $this->validatePayloadHash($manifest, 'countries', $countriesFile);
        $this->validatePayloadHash($manifest, 'translations', $translationsFile);

        /** @var list<array{alpha2: string, alpha3: string, numeric_code: string, canonical_name: string, region_code: string|null, subregion_code: string|null, intermediate_region_code: string|null}> $countries */
        /** @var list<array{alpha2: string, locale: string, name: string}> $translations */
        return compact('manifest', 'countries', 'translations');
    }

    /** @return array<string, mixed> */
    private function decode(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Reference dataset file is not readable: {$path}");
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid JSON in reference dataset file {$path}: {$exception->getMessage()}", previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException("Reference dataset file must contain a JSON object: {$path}");
        }

        return $decoded;
    }

    /** @param array<string, mixed> $manifest */
    private function payloadPath(string $directory, array $manifest, string $key): string
    {
        $file = $manifest['payloads'][$key]['file'] ?? null;

        if (! is_string($file) || $file === '' || basename($file) !== $file) {
            throw new RuntimeException("Manifest payload {$key} must name a local versioned file.");
        }

        return $directory.'/'.$file;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  list<mixed>  $countries
     * @param  list<mixed>  $translations
     */
    private function validateManifest(array $manifest, array $countries, array $translations): void
    {
        $version = $manifest['dataset_version'] ?? null;
        $countryCount = $manifest['expected_country_count'] ?? null;
        $translationCount = $manifest['expected_translation_count'] ?? null;
        $locales = $manifest['reference_locales'] ?? null;

        if (! is_string($version) || preg_match('/\Acountries-v\d+\z/', $version) !== 1) {
            throw new RuntimeException('Manifest dataset_version must be a versioned countries-vN identifier.');
        }

        if (! is_int($countryCount) || $countryCount < 200 || $countryCount !== count($countries)) {
            throw new RuntimeException('Country count does not match the sane expected_country_count in the manifest.');
        }

        if (! is_int($translationCount) || $translationCount !== count($translations)) {
            throw new RuntimeException('Translation count does not match expected_translation_count in the manifest.');
        }

        if (! is_array($locales) || ! array_is_list($locales) || $locales === []) {
            throw new RuntimeException('Manifest reference_locales must be a non-empty list.');
        }

        $seenLocales = [];
        foreach ($locales as $locale) {
            if (! is_string($locale) || preg_match('/\A[a-z]{2,3}(?:-[A-Za-z0-9]{2,8})*\z/', $locale) !== 1) {
                throw new RuntimeException('Manifest contains an invalid reference locale.');
            }

            if (isset($seenLocales[$locale])) {
                throw new RuntimeException("Manifest contains duplicate reference locale {$locale}.");
            }

            $seenLocales[$locale] = true;
        }
    }

    /** @param list<mixed> $countries */
    private function validateCountries(array $countries): void
    {
        $seen = ['alpha2' => [], 'alpha3' => [], 'numeric_code' => []];

        foreach ($countries as $index => $country) {
            if (! is_array($country)) {
                throw new RuntimeException("Country record {$index} must be an object.");
            }

            $this->requiredPattern($country, 'alpha2', '/\A[A-Z]{2}\z/', $index);
            $this->requiredPattern($country, 'alpha3', '/\A[A-Z]{3}\z/', $index);
            $this->requiredPattern($country, 'numeric_code', '/\A\d{3}\z/', $index);
            $this->requiredText($country, 'canonical_name', $index);

            foreach (['region_code', 'subregion_code', 'intermediate_region_code'] as $field) {
                $value = $country[$field] ?? null;

                if ($value !== null && (! is_string($value) || preg_match('/\A\d{3}\z/', $value) !== 1)) {
                    throw new RuntimeException("Country record {$index} has an invalid {$field}.");
                }
            }

            foreach (array_keys($seen) as $field) {
                $value = $country[$field];
                assert(is_string($value));

                if (isset($seen[$field][$value])) {
                    throw new RuntimeException("Duplicate country {$field}: {$value}.");
                }

                $seen[$field][$value] = true;
            }
        }
    }

    /**
     * @param  list<mixed>  $translations
     * @param  list<mixed>  $countries
     * @param  array<string, mixed>  $manifest
     */
    private function validateTranslations(array $translations, array $countries, array $manifest): void
    {
        $countryCodes = [];
        foreach ($countries as $country) {
            assert(is_array($country) && is_string($country['alpha2'] ?? null));
            $countryCodes[$country['alpha2']] = true;
        }

        $locales = $manifest['reference_locales'];
        assert(is_array($locales));
        $allowedLocales = array_fill_keys($locales, true);
        $seen = [];

        foreach ($translations as $index => $translation) {
            if (! is_array($translation)) {
                throw new RuntimeException("Country translation {$index} must be an object.");
            }

            $this->requiredPattern($translation, 'alpha2', '/\A[A-Z]{2}\z/', $index);
            $this->requiredPattern($translation, 'locale', '/\A[a-z]{2,3}(?:-[A-Za-z0-9]{2,8})*\z/', $index);
            $this->requiredText($translation, 'name', $index);
            $alpha2 = $translation['alpha2'];
            $locale = $translation['locale'];
            assert(is_string($alpha2) && is_string($locale));

            if (! isset($countryCodes[$alpha2])) {
                throw new RuntimeException("Country translation {$index} references unknown country {$alpha2}.");
            }

            if (! isset($allowedLocales[$locale])) {
                throw new RuntimeException("Country translation {$index} uses undeclared locale {$locale}.");
            }

            $key = $alpha2."\0".$locale;
            if (isset($seen[$key])) {
                throw new RuntimeException("Duplicate country translation for {$alpha2} and {$locale}.");
            }
            $seen[$key] = true;
        }
    }

    /** @param array<string, mixed> $record */
    private function requiredPattern(array $record, string $field, string $pattern, int $index): void
    {
        $value = $record[$field] ?? null;

        if (! is_string($value) || preg_match($pattern, $value) !== 1) {
            throw new RuntimeException("Reference record {$index} has an invalid {$field}.");
        }
    }

    /** @param array<string, mixed> $record */
    private function requiredText(array $record, string $field, int $index): void
    {
        $value = $record[$field] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException("Reference record {$index} has a blank {$field}.");
        }
    }

    /** @param array<string, mixed> $manifest */
    private function validatePayloadHash(array $manifest, string $key, string $path): void
    {
        $expected = $manifest['payloads'][$key]['sha256'] ?? null;

        if (! is_string($expected) || preg_match('/\A[a-f0-9]{64}\z/', $expected) !== 1) {
            throw new RuntimeException("Manifest payload {$key} must include a SHA-256 hash.");
        }

        if (! hash_equals($expected, hash_file('sha256', $path))) {
            throw new RuntimeException("Reference payload hash mismatch for {$key}.");
        }
    }
}
