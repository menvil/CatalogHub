<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ClockFixtures;
use Tests\Support\SiteFixtures;
use Tests\Support\UiFixture;
use Tests\TestCase;

final class TestSupportTest extends TestCase
{
    use ClockFixtures;
    use RefreshDatabase;
    use SiteFixtures;

    public function test_site_helper_builds_an_explicit_runtime_context(): void
    {
        $site = $this->foundationSite('alpha', ['en-US', 'de-DE']);

        self::assertSame('fixture-alpha', $site->code);
        self::assertSame('fixture-alpha.test', $site->primaryDomain?->host);
        self::assertSame(['en-US', 'de-DE'], $site->locales()->ordered()->pluck('locale_code')->all());
    }

    public function test_clock_helper_freezes_and_restores_time_and_timezone(): void
    {
        $originalTimezone = date_default_timezone_get();
        $originalNow = CarbonImmutable::getTestNow();

        $captured = $this->withFoundationClock(static fn (): array => [
            CarbonImmutable::now()->toIso8601String(),
            date_default_timezone_get(),
        ]);

        self::assertSame([UiFixture::CLOCK, UiFixture::TIMEZONE], $captured);
        self::assertSame($originalTimezone, date_default_timezone_get());
        self::assertSame($originalNow, CarbonImmutable::getTestNow());
    }
}
