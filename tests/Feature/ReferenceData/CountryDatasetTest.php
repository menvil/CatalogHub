<?php

declare(strict_types=1);

namespace Tests\Feature\ReferenceData;

use App\Services\ReferenceData\CountryDatasetLoader;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

final class CountryDatasetTest extends TestCase
{
    public function test_committed_dataset_is_valid_and_contains_known_reference_records(): void
    {
        $dataset = app(CountryDatasetLoader::class)->load();
        $manifest = $dataset['manifest'];

        $this->assertSame($manifest['expected_country_count'], count($dataset['countries']));
        $this->assertSame($manifest['expected_translation_count'], count($dataset['translations']));
        $this->assertGreaterThan(200, count($dataset['countries']));
        $this->assertSame(['en', 'de'], $manifest['reference_locales']);

        $countries = collect($dataset['countries'])->keyBy('alpha2');
        $this->assertSame(['DEU', '276', '150', '155'], [
            $countries['DE']['alpha3'],
            $countries['DE']['numeric_code'],
            $countries['DE']['region_code'],
            $countries['DE']['subregion_code'],
        ]);
        $this->assertSame(['JPN', '392'], [$countries['JP']['alpha3'], $countries['JP']['numeric_code']]);
        $this->assertSame(['USA', '840'], [$countries['US']['alpha3'], $countries['US']['numeric_code']]);
        $this->assertSame('South Korea', $countries['KR']['canonical_name']);
    }

    public function test_immutable_v1_migration_manifest_resolves_the_same_versioned_payload(): void
    {
        $dataset = app(CountryDatasetLoader::class)->load(
            database_path('reference-data/countries'),
            'manifest-v1.json',
        );

        $this->assertSame('countries-v1', $dataset['manifest']['dataset_version']);
        $this->assertCount(249, $dataset['countries']);
        $this->assertSame('countries-v1.json', $dataset['manifest']['payloads']['countries']['file']);
        $this->assertSame('country-translations-v1.json', $dataset['manifest']['payloads']['translations']['file']);
    }

    public function test_duplicate_or_malformed_payload_is_rejected(): void
    {
        $directory = $this->temporaryDataset();

        try {
            $countriesPath = $directory.'/countries-v1.json';
            $payload = json_decode((string) file_get_contents($countriesPath), true, 512, JSON_THROW_ON_ERROR);
            $payload['countries'][1]['alpha2'] = $payload['countries'][0]['alpha2'];
            file_put_contents($countriesPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
            $manifestPath = $directory.'/manifest.json';
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $manifest['payloads']['countries']['sha256'] = hash_file('sha256', $countriesPath);
            file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Duplicate country alpha2');
            app(CountryDatasetLoader::class)->load($directory);
        } finally {
            if (is_dir($directory)) {
                File::deleteDirectory($directory);
            }
        }
    }

    private function temporaryDataset(): string
    {
        $directory = sys_get_temp_dir().'/cataloghub-country-dataset-'.bin2hex(random_bytes(8));
        $this->assertTrue(File::copyDirectory(
            database_path('reference-data/countries'),
            $directory,
        ));

        return $directory;
    }
}
