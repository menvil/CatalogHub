<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class CentralBrandsNameIdentityMigrationTest extends TestCase
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

    public function test_backfills_unicode_name_identity_for_existing_brands(): void
    {
        $migration = $this->rollBackNameIdentityMigration();
        $this->insertLegacyBrand("E\u{0301}LECTRO", 'electro');

        $migration->up();

        $brand = DB::table('central_brands')->where('slug', 'electro')->first();
        self::assertNotNull($brand);
        self::assertSame('ÉLECTRO', $brand->name);
        self::assertSame('électro', $brand->normalized_name);
        self::assertSame(hash('sha256', 'électro'), $brand->normalized_name_hash);
    }

    public function test_duplicate_legacy_names_fail_before_schema_mutation_and_can_be_corrected_and_rerun(): void
    {
        $migration = $this->rollBackNameIdentityMigration();
        $this->insertLegacyBrand('ÉLECTRO', 'electro-one');
        $this->insertLegacyBrand('électro', 'electro-two');

        try {
            $migration->up();
            self::fail('Equivalent Unicode canonical names were accepted by the migration.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('duplicate Unicode-normalized canonical names', $exception->getMessage());
        }

        self::assertFalse(Schema::hasColumn('central_brands', 'normalized_name'));
        self::assertFalse(Schema::hasColumn('central_brands', 'normalized_name_hash'));

        DB::table('central_brands')->where('slug', 'electro-two')->update(['name' => 'Electro']);
        $migration->up();

        self::assertTrue(Schema::hasColumns('central_brands', ['normalized_name', 'normalized_name_hash']));
        self::assertSame(2, DB::table('central_brands')->distinct()->count('normalized_name_hash'));
    }

    private function rollBackNameIdentityMigration(): object
    {
        $migration = require database_path('migrations/2026_08_13_000002_add_normalized_name_identity_to_central_brands_table.php');
        $migration->down();

        return $migration;
    }

    private function insertLegacyBrand(string $name, string $slug): void
    {
        DB::table('central_brands')->insert([
            'name' => $name,
            'slug' => $slug,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
