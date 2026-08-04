<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Site;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
}
