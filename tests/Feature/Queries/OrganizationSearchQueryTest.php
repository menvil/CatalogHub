<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Models\Organization;
use App\Queries\CentralCatalog\OrganizationSearchQuery;
use App\Support\Normalization\OrganizationNameNormalizer;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class OrganizationSearchQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_uses_one_bounded_query_with_stable_name_and_id_ordering(): void
    {
        $duplicates = collect(range(1, 21))
            ->map(static fn (): Organization => Organization::factory()->create(['name' => 'Same Name']));
        $beyondNameLimit = $duplicates->last();
        self::assertInstanceOf(Organization::class, $beyondNameLimit);
        Organization::factory()->create(['name' => 'Aardvark']);
        Organization::factory()->create(['name' => 'Zulu']);
        $queryCount = 0;
        DB::listen(static function (QueryExecuted $query) use (&$queryCount): void {
            if (str_contains($query->sql, 'organizations')) {
                $queryCount++;
            }
        });

        $results = app(OrganizationSearchQuery::class)->search('same');

        self::assertSame(1, $queryCount);
        self::assertCount(OrganizationSearchQuery::LIMIT, $results);
        $expectedPrefix = OrganizationNameNormalizer::search('same');
        self::assertTrue(collect($results)->every(
            static fn (array $option): bool => str_starts_with($option['search'], $expectedPrefix),
        ));
        self::assertCount(OrganizationSearchQuery::LIMIT, collect($results)->pluck('label')->unique());
        self::assertTrue(collect($results)->every(
            static fn (array $option): bool => $option['label'] === sprintf(
                'Same Name — Organization #%s',
                $option['value'],
            ),
        ));
        self::assertNotContains((string) $beyondNameLimit->id, collect($results)->pluck('value')->all());
        self::assertSame(
            collect($results)->pluck('value')->map(static fn (string $id): int => (int) $id)->sort()->values()->all(),
            collect($results)->pluck('value')->map(static fn (string $id): int => (int) $id)->values()->all(),
        );

        self::assertSame(
            [app(OrganizationSearchQuery::class)->option($beyondNameLimit)],
            app(OrganizationSearchQuery::class)->search('#'.$beyondNameLimit->id),
        );
    }

    public function test_search_treats_like_metacharacters_as_literal_prefix_characters(): void
    {
        $organizations = collect([
            'Acme% Holdings',
            'AcmeX Holdings',
            'Unit_ Group',
            'UnitX Group',
            'Bang! Parent',
            'BangX Parent',
        ])->mapWithKeys(static fn (string $name): array => [
            $name => Organization::factory()->create(['name' => $name]),
        ]);

        foreach ([
            'Acme%' => ['Acme% Holdings'],
            'Unit_' => ['Unit_ Group'],
            'Bang!' => ['Bang! Parent'],
        ] as $prefix => $expectedLabels) {
            self::assertSame(
                collect($expectedLabels)
                    ->map(fn (string $label): string => app(OrganizationSearchQuery::class)
                        ->option($organizations->get($label))['label'])
                    ->all(),
                collect(app(OrganizationSearchQuery::class)->search($prefix))->pluck('label')->all(),
            );
        }
    }
}
