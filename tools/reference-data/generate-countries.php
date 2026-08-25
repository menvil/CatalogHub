#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Normalize pinned developer-downloaded UN M49 and CLDR inputs.
 *
 * Usage:
 * php tools/reference-data/generate-countries.php \
 *   --m49=/tmp/m49.html \
 *   --cldr-en=/tmp/cldr-en.json \
 *   --cldr-de=/tmp/cldr-de.json \
 *   --output=database/reference-data/countries
 */
$options = getopt('', ['m49:', 'cldr-en:', 'cldr-de:', 'output:']);

foreach (['m49', 'cldr-en', 'cldr-de', 'output'] as $required) {
    if (! isset($options[$required]) || ! is_string($options[$required]) || trim($options[$required]) === '') {
        fwrite(STDERR, "Missing required --{$required} option.\n");
        exit(1);
    }
}

/** @return array<string, mixed> */
function decodeJsonFile(string $path): array
{
    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Unable to read {$path}.");
    }

    $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

    if (! is_array($decoded)) {
        throw new RuntimeException("Expected an object in {$path}.");
    }

    return $decoded;
}

/** @return array<string, string> */
function cldrTerritories(string $path, string $locale): array
{
    $decoded = decodeJsonFile($path);
    $territories = $decoded['main'][$locale]['localeDisplayNames']['territories'] ?? null;

    if (! is_array($territories)) {
        throw new RuntimeException("Missing CLDR territory names for {$locale} in {$path}.");
    }

    return array_filter(
        $territories,
        static fn (mixed $name, mixed $code): bool => is_string($code)
            && preg_match('/\A[A-Z]{2}\z/', $code) === 1
            && is_string($name)
            && trim($name) !== '',
        ARRAY_FILTER_USE_BOTH,
    );
}

/** @return list<array{alpha2: string, alpha3: string, numeric_code: string, canonical_name: string, region_code: string|null, subregion_code: string|null, intermediate_region_code: string|null}> */
function m49Countries(string $path, array $englishNames): array
{
    $document = new DOMDocument;
    libxml_use_internal_errors(true);

    if (! $document->loadHTMLFile($path, LIBXML_NOERROR | LIBXML_NOWARNING)) {
        throw new RuntimeException("Unable to parse {$path}.");
    }

    libxml_clear_errors();
    $xpath = new DOMXPath($document);
    $records = [];

    foreach ($xpath->query('//tr') ?: [] as $row) {
        $cells = $xpath->query('./td', $row);

        if ($cells === false || $cells->length < 12) {
            continue;
        }

        $values = [];
        foreach ($cells as $cell) {
            $values[] = trim(preg_replace('/\s+/u', ' ', $cell->textContent) ?? '');
        }

        $numeric = $values[9] ?? '';
        $alpha2 = $values[10] ?? '';
        $alpha3 = $values[11] ?? '';

        if (preg_match('/\A\d{3}\z/', $numeric) !== 1
            || preg_match('/\A[A-Z]{2}\z/', $alpha2) !== 1
            || preg_match('/\A[A-Z]{3}\z/', $alpha3) !== 1
            || isset($records[$alpha2])) {
            continue;
        }

        $canonicalName = $englishNames[$alpha2] ?? null;

        if (! is_string($canonicalName) || trim($canonicalName) === '') {
            throw new RuntimeException("CLDR English name is missing for M49 country {$alpha2}.");
        }

        $records[$alpha2] = [
            'alpha2' => $alpha2,
            'alpha3' => $alpha3,
            'numeric_code' => $numeric,
            'canonical_name' => trim($canonicalName),
            'region_code' => ($values[2] ?? '') !== '' ? $values[2] : null,
            'subregion_code' => ($values[4] ?? '') !== '' ? $values[4] : null,
            'intermediate_region_code' => ($values[6] ?? '') !== '' ? $values[6] : null,
        ];
    }

    ksort($records, SORT_STRING);

    return array_values($records);
}

function writeJson(string $path, mixed $value): void
{
    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";

    if (file_put_contents($path, $json) === false) {
        throw new RuntimeException("Unable to write {$path}.");
    }
}

$m49Path = $options['m49'];
$cldrPaths = ['en' => $options['cldr-en'], 'de' => $options['cldr-de']];
$output = rtrim($options['output'], '/');
$namesByLocale = [];

foreach ($cldrPaths as $locale => $path) {
    $namesByLocale[$locale] = cldrTerritories($path, $locale);
}

$countries = m49Countries($m49Path, $namesByLocale['en']);

// The current UNSD overview folds TW into China and therefore exposes 248 rows,
// while ISO 3166-1 still assigns TW/TWN and existing CatalogHub data uses it.
// The public UN M49 Rev.4 table records numeric code 158; CLDR supplies the name.
if (! in_array('TW', array_column($countries, 'alpha2'), true)) {
    $countries[] = [
        'alpha2' => 'TW',
        'alpha3' => 'TWN',
        'numeric_code' => '158',
        'canonical_name' => $namesByLocale['en']['TW'],
        'region_code' => '142',
        'subregion_code' => '030',
        'intermediate_region_code' => null,
    ];
    usort($countries, static fn (array $left, array $right): int => $left['alpha2'] <=> $right['alpha2']);
}

if (count($countries) < 200) {
    throw new RuntimeException('Normalized M49 country/area count is implausibly low: '.count($countries).'.');
}

$translations = [];
foreach ($countries as $country) {
    foreach ($namesByLocale as $locale => $names) {
        $name = $names[$country['alpha2']] ?? null;

        if (! is_string($name) || trim($name) === '') {
            throw new RuntimeException("Missing {$locale} translation for {$country['alpha2']}.");
        }

        $translations[] = [
            'alpha2' => $country['alpha2'],
            'locale' => $locale,
            'name' => trim($name),
        ];
    }
}

if (! is_dir($output) && ! mkdir($output, 0755, true) && ! is_dir($output)) {
    throw new RuntimeException("Unable to create {$output}.");
}

$countriesFile = $output.'/countries-v1.json';
$translationsFile = $output.'/country-translations-v1.json';
writeJson($countriesFile, ['countries' => $countries]);
writeJson($translationsFile, ['translations' => $translations]);

$manifest = [
    'dataset_version' => 'countries-v1',
    'expected_country_count' => count($countries),
    'expected_translation_count' => count($translations),
    'reference_locales' => array_keys($namesByLocale),
    'sources' => [
        'un_m49' => [
            'snapshot_date' => '2026-08-25',
            'url' => 'https://unstats.un.org/unsd/methodology/m49/overview',
            'sha256' => hash_file('sha256', $m49Path),
            'usage' => 'M49 numeric, ISO alpha-2/alpha-3, region, subregion, and intermediate-region codes',
        ],
        'iso_3166' => [
            'standard' => 'ISO 3166-1:2020 (reviewed and confirmed 2025)',
            'url' => 'https://www.iso.org/iso-3166-country-codes.html',
            'usage' => 'Current officially assigned alpha-2/alpha-3 identities; ISO permits free use of its country codes',
            'supplement' => [
                'alpha2' => 'TW',
                'alpha3' => 'TWN',
                'numeric_code' => '158',
                'un_m49_reference' => 'https://unstats.un.org/unsd/publication/SeriesM/Series_M49_Rev4%281999%29_en.pdf',
                'reason' => 'Current UNSD overview omits a separate row while ISO 3166-1 retains the identity',
            ],
        ],
        'cldr' => [
            'version' => '48.2',
            'json_package_version' => '48.2.0',
            'release_date' => '2026-03-17',
            'repository_tag' => '48.2.0',
            'license' => 'Unicode-3.0',
            'files' => array_map(
                static fn (string $path): array => ['sha256' => hash_file('sha256', $path)],
                $cldrPaths,
            ),
            'usage' => 'Localized territory display names and canonical English display name',
        ],
    ],
    'payloads' => [
        'countries' => ['file' => basename($countriesFile), 'sha256' => hash_file('sha256', $countriesFile)],
        'translations' => ['file' => basename($translationsFile), 'sha256' => hash_file('sha256', $translationsFile)],
    ],
];

writeJson($output.'/manifest-v1.json', $manifest);
writeJson($output.'/manifest.json', $manifest);

fwrite(STDOUT, sprintf(
    "Generated %d countries and %d translations (%s).\n",
    count($countries),
    count($translations),
    implode(', ', array_keys($namesByLocale)),
));
