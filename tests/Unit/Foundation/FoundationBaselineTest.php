<?php

declare(strict_types=1);

namespace Tests\Unit\Foundation;

use App\Enums\PriceSourceType;
use App\Enums\SiteDomainType;
use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\Market;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Pricing\Adapters\CsvFeedPriceAdapter;
use App\Pricing\Adapters\GenericApiPriceAdapter;
use App\Pricing\Adapters\ManualOfferAdapter;
use App\Pricing\PriceSourceAdapterRegistry;
use App\Services\Corrections\CanonicalCorrectionFieldResolver;
use App\Services\Pricing\OutboundPriceSourceUrlGuard;
use App\Services\Pricing\PriceSourceCredentialService;
use App\Support\Normalization\CodeNormalizer;
use App\Support\Normalization\HostNormalizer;
use App\Support\Normalization\LocaleNormalizer;
use App\Support\Normalization\SlugNormalizer;
use App\Support\Sites\SiteRuntimeContext;
use PHPUnit\Framework\TestCase;
use Tests\Support\ScreenshotNaming;

final class FoundationBaselineTest extends TestCase
{
    public function test_foundation_enums_publish_stable_defaults_and_states(): void
    {
        self::assertSame(UserRole::CatalogEditor, UserRole::default());
        self::assertSame(SiteStatus::Draft, SiteStatus::default());
        self::assertTrue(SiteStatus::Active->isPubliclyAvailable());
        self::assertFalse(SiteStatus::Archived->allowsAdministration());
        self::assertSame(['single_category', 'multi_category'], array_column(SiteMode::cases(), 'value'));
    }

    public function test_foundation_normalizers_are_pure_and_deterministic(): void
    {
        self::assertSame('catalog-hub', CodeNormalizer::normalize(' Catalog_Hub '));
        self::assertSame('catalog.example.test', HostNormalizer::normalize('HTTPS://Catalog.Example.Test./path'));
        self::assertSame('en-US', LocaleNormalizer::normalize('EN_us'));
        self::assertSame('catalog-hub', SlugNormalizer::normalize(' Catalog__Hub '));
    }

    public function test_visual_reference_naming_is_deterministic(): void
    {
        self::assertSame(
            'tests/Visual/baselines/z-001__default__1280x900.png',
            ScreenshotNaming::referencePath('Z-001', 'default', 1280, 900),
        );
    }

    public function test_context_resolver_and_registry_baselines_are_pure(): void
    {
        $market = new Market(['code' => 'fixture-market']);
        $market->setAttribute('id', 10);
        $site = new Site(['market_id' => 10, 'code' => 'fixture-site']);
        $site->setAttribute('id', 20);
        $domain = new SiteDomain([
            'site_id' => 20,
            'host' => 'fixture-site.test',
            'type' => SiteDomainType::Primary,
        ]);
        $domain->setAttribute('id', 30);
        $context = new SiteRuntimeContext(
            $site,
            $domain,
            $market,
            null,
            'en-US',
            'EUR',
            'UTC',
        );

        self::assertSame('fixture-site', $context->__debugInfo()['site_code']);
        self::assertTrue((new CanonicalCorrectionFieldResolver)->supports('name'));

        $guard = new OutboundPriceSourceUrlGuard(static fn (): array => ['1.1.1.1']);
        $manual = new ManualOfferAdapter;
        $registry = new PriceSourceAdapterRegistry(
            $manual,
            new CsvFeedPriceAdapter($guard),
            new GenericApiPriceAdapter(new PriceSourceCredentialService, $guard),
        );
        $source = new PriceSource(['type' => PriceSourceType::Manual]);
        $source->setAttribute('id', 40);

        self::assertSame($manual, $registry->for($source));
    }
}
