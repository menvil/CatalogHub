<?php

declare(strict_types=1);

namespace Tests\Feature\Factories;

use App\Models\Site;
use App\Models\SiteLocale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

final class SiteRuntimeFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_locale_factory_creates_a_valid_default_runtime_locale(): void
    {
        $siteLocale = SiteLocale::factory()->create();
        $site = $siteLocale->site;

        self::assertTrue($siteLocale->is_default);
        self::assertTrue($siteLocale->is_enabled);
        self::assertSame('en-US', $site->default_locale);
        self::assertSame('en-US', $site->defaultLocale?->locale_code);
    }

    public function test_site_runtime_state_rejects_an_empty_locale_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Site::factory()->withRuntimeContext([]);
    }

    public function test_site_runtime_state_rejects_a_default_outside_the_locale_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Site::factory()->withRuntimeContext(['en-US'], 'de-DE');
    }

    public function test_site_runtime_state_rejects_duplicate_locale_codes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Site::factory()->withRuntimeContext(['en-US', 'en-US']);
    }

    public function test_site_admin_state_only_creates_runtime_rows_after_the_user_is_persisted(): void
    {
        $site = Site::factory()->create();
        $site->domains()->delete();
        $site->locales()->delete();

        User::factory()->siteAdmin($site)->make();

        self::assertSame(0, $site->domains()->count());
        self::assertSame(0, $site->locales()->count());

        User::factory()->siteAdmin($site)->create();

        self::assertSame(1, $site->domains()->where('is_primary', true)->where('is_active', true)->count());
        self::assertSame(1, $site->locales()->where('is_default', true)->where('is_enabled', true)->count());
        self::assertFalse($site->defaultLocale?->locale?->is_default);
    }

    public function test_site_admin_state_rejects_a_site_without_a_usable_primary_domain(): void
    {
        $site = Site::factory()->create(['domain' => null]);

        try {
            User::factory()->siteAdmin($site)->create();
            self::fail('A site admin was created without a usable primary domain.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('An active primary site domain is required.', $exception->getMessage());
        }

        self::assertSame(0, User::query()->count());
        self::assertSame(0, $site->domains()->count());
        self::assertSame(0, $site->locales()->count());
    }

    public function test_site_admin_state_rejects_an_invalid_default_locale_code(): void
    {
        $site = Site::factory()->create(['default_locale' => '']);
        $domainCount = $site->domains()->count();

        try {
            User::factory()->siteAdmin($site)->create();
            self::fail('A site admin was created with an invalid default locale.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('A valid default site locale is required.', $exception->getMessage());
        }

        self::assertSame(0, User::query()->count());
        self::assertSame($domainCount, $site->domains()->count());
        self::assertSame(0, $site->locales()->count());
        self::assertDatabaseMissing('locales', ['code' => '']);
    }
}
