<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Site;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class SiteDomainsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_domains_schema_has_the_foundation_contract(): void
    {
        self::assertTrue(Schema::hasColumns('site_domains', [
            'id',
            'site_id',
            'host',
            'type',
            'is_primary',
            'is_active',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('site_domains'));

        self::assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['host'],
        ));
        self::assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'is_active'],
        ));
    }

    public function test_host_is_globally_unique_across_sites(): void
    {
        $first = Site::factory()->create();
        $second = Site::factory()->create();

        DB::table('site_domains')->insert([
            'site_id' => $first->id,
            'host' => 'unique-host.test',
            'type' => 'primary',
            'is_primary' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('site_domains')->insert([
            'site_id' => $second->id,
            'host' => 'unique-host.test',
            'type' => 'alias',
            'is_primary' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_backfill_rejects_equivalent_normalized_hosts_before_inserting_them(): void
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

        self::assertSame(0, DB::table('site_domains')->count());
    }
}
