<?php

declare(strict_types=1);

namespace Tests\Unit\Sites;

use App\Enums\SiteDomainType;
use App\Enums\SiteResolutionMode;
use App\Enums\SiteStatus;
use App\Exceptions\Sites\UnknownSiteException;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Services\Sites\SiteResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SiteResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_and_alias_hosts_resolve_the_same_active_site_with_required_relations(): void
    {
        $site = Site::factory()->active()->withRuntimeContext(['de-DE', 'en-DE'], 'de-DE')->create([
            'domain' => 'primary.example.test',
        ]);
        SiteDomain::factory()->for($site)->create([
            'host' => 'alias.example.test',
            'type' => SiteDomainType::Alias,
        ]);
        $resolver = app(SiteResolver::class);

        $primary = $resolver->resolve('HTTPS://PRIMARY.EXAMPLE.TEST:443/catalog');
        $alias = $resolver->resolve('Alias.Example.Test:8080');

        self::assertTrue($primary->is($site));
        self::assertTrue($alias->is($site));
        self::assertTrue($primary->relationLoaded('market'));
        self::assertTrue($primary->relationLoaded('locales'));
        self::assertTrue($primary->relationLoaded('domains'));
    }

    public function test_unknown_and_inactive_hosts_fail_without_a_default_site_fallback(): void
    {
        Site::factory()->active()->withRuntimeContext()->create(['domain' => 'known.example.test']);
        SiteDomain::factory()->create(['host' => 'inactive.example.test', 'is_active' => false]);
        $resolver = app(SiteResolver::class);

        foreach (['unknown.example.test', 'inactive.example.test'] as $host) {
            try {
                $resolver->resolve($host);
                self::fail("Host [{$host}] unexpectedly resolved.");
            } catch (UnknownSiteException $exception) {
                self::assertSame(404, $exception->getStatusCode());
            }
        }
    }

    #[DataProvider('statusMatrix')]
    public function test_site_status_and_domain_type_are_enforced_by_resolution_mode(
        SiteStatus $status,
        SiteDomainType $domainType,
        SiteResolutionMode $mode,
        bool $allowed,
    ): void {
        $site = Site::factory()->withRuntimeContext()->create([
            'status' => $status,
            'domain' => null,
        ]);
        SiteDomain::factory()->for($site)->create([
            'host' => 'mode.example.test',
            'type' => $domainType,
            'is_primary' => $domainType === SiteDomainType::Primary,
        ]);

        if (! $allowed) {
            $this->expectException(UnknownSiteException::class);
        }

        $resolved = app(SiteResolver::class)->resolve('mode.example.test', $mode);

        if ($allowed) {
            self::assertTrue($resolved->is($site));
        }
    }

    /** @return iterable<string, array{SiteStatus, SiteDomainType, SiteResolutionMode, bool}> */
    public static function statusMatrix(): iterable
    {
        yield 'active primary is public' => [SiteStatus::Active, SiteDomainType::Primary, SiteResolutionMode::Public, true];
        yield 'active alias is public' => [SiteStatus::Active, SiteDomainType::Alias, SiteResolutionMode::Public, true];
        yield 'preview is not public' => [SiteStatus::Active, SiteDomainType::Preview, SiteResolutionMode::Public, false];
        yield 'preview is administrable' => [SiteStatus::Active, SiteDomainType::Preview, SiteResolutionMode::Administration, true];
        yield 'draft is not public' => [SiteStatus::Draft, SiteDomainType::Primary, SiteResolutionMode::Public, false];
        yield 'draft is administrable' => [SiteStatus::Draft, SiteDomainType::Primary, SiteResolutionMode::Administration, true];
        yield 'suspended is administrable' => [SiteStatus::Suspended, SiteDomainType::Primary, SiteResolutionMode::Administration, true];
        yield 'archived is not administrable' => [SiteStatus::Archived, SiteDomainType::Primary, SiteResolutionMode::Administration, false];
    }
}
