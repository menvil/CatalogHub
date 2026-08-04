<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\SiteDomainType;
use App\Models\Site;
use App\Models\SiteDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SiteDomainTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('hostCases')]
    public function test_host_is_normalized_before_storage(string $input, string $expected): void
    {
        $domain = SiteDomain::factory()->for(Site::factory())->create(['host' => $input]);

        self::assertSame($expected, $domain->host);
        self::assertSame($expected, $domain->fresh()?->host);
    }

    /** @return iterable<string, array{string, string}> */
    public static function hostCases(): iterable
    {
        yield 'case' => ['Store.Example.TEST', 'store.example.test'];
        yield 'scheme path and port' => ['https://Store.Example.TEST:8443/catalog?q=1', 'store.example.test'];
        yield 'port without scheme' => ['Store.Example.TEST:8080', 'store.example.test'];
        yield 'trailing dot' => ['store.example.test.', 'store.example.test'];
    }

    public function test_domain_type_and_flags_are_typed(): void
    {
        $domain = SiteDomain::factory()->preview()->create();

        self::assertSame(SiteDomainType::Preview, $domain->type);
        self::assertFalse($domain->is_primary);
        self::assertTrue($domain->is_active);
        self::assertTrue($domain->site instanceof Site);
    }
}
