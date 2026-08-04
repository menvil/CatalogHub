<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Market;
use App\Models\Site;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class SiteDomainsMigrationRecoveryTest extends TestCase
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

    public function test_failed_backfill_can_be_corrected_and_rerun_without_ddl_cleanup(): void
    {
        $first = Site::factory()->create(['domain' => null]);
        $second = Site::factory()->create(['domain' => null]);
        DB::table('sites')->where('id', $first->id)->update(['domain' => 'HTTPS://Duplicate.TEST./path']);
        DB::table('sites')->where('id', $second->id)->update(['domain' => 'duplicate.test']);
        Schema::drop('site_domains');
        $migration = require database_path('migrations/2026_08_04_000002_create_site_domains_table.php');

        try {
            $migration->up();
            self::fail('Equivalent normalized hosts were accepted by the migration.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Duplicate normalized site domain [duplicate.test]', $exception->getMessage());
        }

        self::assertFalse(Schema::hasTable('site_domains'));

        DB::table('sites')->where('id', $second->id)->update(['domain' => 'corrected.test']);
        $migration->up();

        self::assertTrue(Schema::hasTable('site_domains'));
        self::assertSame([
            'corrected.test',
            'duplicate.test',
        ], DB::table('site_domains')->orderBy('host')->pluck('host')->all());
    }

    public function test_large_backfill_is_chunked_inside_a_transaction(): void
    {
        $market = Market::factory()->create();
        Site::factory()->count(205)->for($market)->create();
        Schema::drop('site_domains');
        $bindingCounts = [];
        $transactionLevels = [];
        DB::listen(function (QueryExecuted $query) use (&$bindingCounts, &$transactionLevels): void {
            if (preg_match('/insert into [`"]?site_domains/i', $query->sql) !== 1) {
                return;
            }

            $bindingCounts[] = count($query->bindings);
            $transactionLevels[] = DB::transactionLevel();
        });
        $migration = require database_path('migrations/2026_08_04_000002_create_site_domains_table.php');

        $migration->up();

        self::assertSame(205, DB::table('site_domains')->count());
        self::assertGreaterThan(1, count($bindingCounts));
        self::assertNotContains(0, $transactionLevels);

        foreach ($bindingCounts as $bindingCount) {
            self::assertLessThanOrEqual(700, $bindingCount);
        }
    }
}
