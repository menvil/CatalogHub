<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class CountryReferenceMigrationRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        try {
            $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
        } finally {
            parent::tearDown();
        }
    }

    public function test_failed_reference_provisioning_drops_both_tables_and_preserves_the_original_failure(): void
    {
        $brandMigration = require database_path('migrations/2026_08_25_000003_replace_brand_country_code_with_country_id.php');
        $countryMigration = require database_path('migrations/2026_08_25_000002_create_country_reference_tables.php');
        $brandMigration->down();
        $countryMigration->down();
        $directory = $this->invalidDataset();

        try {
            $countryMigration->provision($directory, 'manifest-v1.json');
            $this->fail('Invalid reference data was provisioned.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Invalid JSON in reference dataset file', $exception->getMessage());
            $this->assertFalse(Schema::hasTable('country_translations'));
            $this->assertFalse(Schema::hasTable('countries'));
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_brand_cutover_refuses_live_production_traffic(): void
    {
        app()->detectEnvironment(static fn (): string => 'production');
        $migration = require database_path('migrations/2026_08_25_000003_replace_brand_country_code_with_country_id.php');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires production maintenance mode and suspended Brand-mutating workers');

        try {
            $migration->up();
        } finally {
            app()->detectEnvironment(static fn (): string => 'testing');
        }
    }

    private function invalidDataset(): string
    {
        $directory = sys_get_temp_dir().'/cataloghub-country-migration-'.bin2hex(random_bytes(8));
        $this->assertTrue(File::copyDirectory(database_path('reference-data/countries'), $directory));
        file_put_contents($directory.'/countries-v1.json', '{"countries":');

        return $directory;
    }
}
