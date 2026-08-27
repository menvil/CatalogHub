<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\Imports\ImportSource;
use App\Queries\Imports\CentralBrandExternalIdentityQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\DatabaseQueryCounter;
use Tests\TestCase;

final class CentralBrandExternalIdentityQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_loading_is_deterministic_bounded_and_never_selects_source_config(): void
    {
        $brand = CentralBrand::factory()->create();
        $sources = collect([
            ['Zulu Source', 'zulu'],
            ['Alpha Source', 'alpha'],
            ['Alpha Source', 'alpha_second'],
        ])->map(fn (array $values): ImportSource => ImportSource::factory()->create([
            'name' => $values[0],
            'code' => $values[1],
            'config_json' => ['token' => 'must-not-load'],
        ]));

        foreach (range(1, 30) as $index) {
            CentralBrandExternalIdentity::factory()
                ->for($brand, 'brand')
                ->for($sources[$index % $sources->count()], 'source')
                ->externalId(sprintf('ID-%03d', $index))
                ->create();
        }

        $measurement = DatabaseQueryCounter::measure(
            fn (): CentralBrand => app(CentralBrandExternalIdentityQuery::class)->loadForBrand($brand),
        );

        self::assertLessThanOrEqual(2, $measurement['count']);
        self::assertCount(30, $measurement['result']->externalIdentities);
        self::assertSame('Alpha Source', $measurement['result']->externalIdentities->firstOrFail()->source->name);
        self::assertTrue($measurement['result']->externalIdentities->every(
            static fn (CentralBrandExternalIdentity $identity): bool => ! array_key_exists('config_json', $identity->source->getAttributes()),
        ));
    }
}
