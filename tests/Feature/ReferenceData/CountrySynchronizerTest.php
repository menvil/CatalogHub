<?php

declare(strict_types=1);

namespace Tests\Feature\ReferenceData;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\Geography\Country;
use App\Models\Geography\CountryTranslation;
use App\Services\ReferenceData\CountryDatasetLoader;
use App\Services\ReferenceData\CountrySynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

final class CountrySynchronizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_the_manifest_counts_and_is_idempotent(): void
    {
        CountryTranslation::query()->delete();
        Country::query()->delete();
        $manifest = app(CountryDatasetLoader::class)->load()['manifest'];
        $synchronizer = app(CountrySynchronizer::class);

        $created = $synchronizer->sync();
        $second = $synchronizer->sync();

        $this->assertSame($manifest['expected_country_count'], $created->created);
        $this->assertSame($manifest['expected_translation_count'], $created->translationsCreated);
        $this->assertSame($manifest['expected_country_count'], Country::query()->count());
        $this->assertSame($manifest['expected_translation_count'], CountryTranslation::query()->count());
        $this->assertSame(0, Country::query()->where('is_active', false)->count());
        $this->assertSame(0, $second->created);
        $this->assertSame($manifest['expected_country_count'], $second->unchanged);
        $this->assertSame($manifest['expected_translation_count'], $second->translationsUnchanged);
    }

    public function test_sync_updates_metadata_and_deactivates_removed_database_rows_without_deleting_them(): void
    {
        $germany = Country::query()->where('alpha2', 'DE')->sole();
        $germanyId = $germany->id;
        $germany->update(['canonical_name' => 'Old Germany']);
        $germanName = $germany->translations()->where('locale', 'de')->sole();
        $germanName->update(['name' => 'Altes Deutschland']);
        $extra = Country::query()->create([
            'alpha2' => 'ZZ',
            'alpha3' => 'ZZZ',
            'numeric_code' => '999',
            'canonical_name' => 'Historical Area',
            'is_active' => true,
        ]);
        $brand = CentralBrand::factory()->create(['country_id' => $extra->id]);

        $result = app(CountrySynchronizer::class)->sync();

        $this->assertGreaterThanOrEqual(1, $result->updated);
        $this->assertGreaterThanOrEqual(1, $result->translationsUpdated);
        $this->assertSame(1, $result->deactivated);
        $this->assertSame($germanyId, Country::query()->where('alpha2', 'DE')->sole()->id);
        $this->assertSame('Germany', Country::query()->where('alpha2', 'DE')->sole()->canonical_name);
        $this->assertSame('Deutschland', $germanName->fresh()->name);
        $this->assertFalse($extra->fresh()->is_active);
        $this->assertSame($extra->id, $brand->fresh()->country_id);
    }

    public function test_dry_run_reports_changes_without_writing(): void
    {
        $germany = Country::query()->where('alpha2', 'DE')->sole();
        $germany->update(['canonical_name' => 'Old Germany']);

        $result = app(CountrySynchronizer::class)->sync(dryRun: true);

        $this->assertTrue($result->dryRun);
        $this->assertGreaterThanOrEqual(1, $result->updated);
        $this->assertSame('Old Germany', $germany->fresh()->canonical_name);
    }

    public function test_dry_run_command_reports_all_operational_counters(): void
    {
        $this->assertSame(0, Artisan::call('reference:countries:sync', ['--dry-run' => true]));
        $output = Artisan::output();

        foreach ([
            'Countries created',
            'Countries updated',
            'Countries unchanged',
            'Countries deactivated',
            'Translations created',
            'Translations updated',
            'Translations unchanged',
        ] as $metric) {
            $this->assertStringContainsString($metric, $output);
        }
    }

    public function test_invalid_dataset_fails_before_database_mutation(): void
    {
        $before = Country::query()->orderBy('alpha2')->pluck('canonical_name', 'alpha2')->all();
        $directory = $this->invalidDataset();

        try {
            app(CountrySynchronizer::class)->sync($directory);
            $this->fail('Invalid reference payload was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Duplicate country alpha2', $exception->getMessage());
            $this->assertSame($before, Country::query()->orderBy('alpha2')->pluck('canonical_name', 'alpha2')->all());
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function invalidDataset(): string
    {
        $directory = sys_get_temp_dir().'/cataloghub-country-sync-'.bin2hex(random_bytes(8));
        File::copyDirectory(database_path('reference-data/countries'), $directory);
        $countriesPath = $directory.'/countries-v1.json';
        $payload = json_decode((string) file_get_contents($countriesPath), true, 512, JSON_THROW_ON_ERROR);
        $payload['countries'][1]['alpha2'] = $payload['countries'][0]['alpha2'];
        file_put_contents($countriesPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
        $manifestPath = $directory.'/manifest.json';
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $manifest['payloads']['countries']['sha256'] = hash_file('sha256', $countriesPath);
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

        return $directory;
    }
}
