<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Models\Organization;
use App\Queries\CentralCatalog\OrganizationSearchQuery;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class OrganizationSearchQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_uses_one_bounded_query_with_stable_name_and_id_ordering(): void
    {
        foreach (range(1, 30) as $index) {
            Organization::factory()->create(['name' => $index % 2 === 0 ? 'Same Name' : sprintf('Other %02d', $index)]);
        }
        $queryCount = 0;
        DB::listen(static function (QueryExecuted $query) use (&$queryCount): void {
            if (str_contains($query->sql, 'organizations')) {
                $queryCount++;
            }
        });

        $results = app(OrganizationSearchQuery::class)->search('same');

        self::assertSame(1, $queryCount);
        self::assertLessThanOrEqual(OrganizationSearchQuery::LIMIT, count($results));
        self::assertSame(
            collect($results)->pluck('value')->map(static fn (string $id): int => (int) $id)->sort()->values()->all(),
            collect($results)->pluck('value')->map(static fn (string $id): int => (int) $id)->values()->all(),
        );
    }
}
