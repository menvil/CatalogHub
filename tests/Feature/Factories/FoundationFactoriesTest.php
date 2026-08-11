<?php

declare(strict_types=1);

namespace Tests\Feature\Factories;

use App\Enums\SiteDomainType;
use App\Enums\SiteMembershipRole;
use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\AuditLogEntry;
use App\Models\Locale;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Models\SiteLocale;
use App\Models\SiteMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FoundationVisualFixture;
use Tests\TestCase;

final class FoundationFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_defaults_create_a_valid_graph_without_seeding(): void
    {
        $site = Site::factory()->active()->multi()->withRuntimeContext()->create();
        $user = User::factory()->siteOwner($site)->create();

        self::assertSame(SiteStatus::Active, $site->status);
        self::assertSame(SiteMode::MultiCategory, $site->mode);
        self::assertNotNull($site->market);
        self::assertNotNull($site->primaryDomain);
        self::assertSame('en-US', $site->defaultLocale?->locale_code);
        self::assertSame(UserRole::SiteAdmin, $user->role);
        self::assertSame(
            SiteMembershipRole::SiteAdmin,
            $user->memberships()->whereBelongsTo($site)->sole()->role,
        );
    }

    public function test_named_status_mode_and_access_states_are_explicit(): void
    {
        $archivedSingle = Site::factory()->archived()->single()->create();
        $centralAdmin = User::factory()->centralAdmin()->create();
        $disabled = User::factory()->disabled()->create();
        $membership = SiteMembership::factory()->siteOwner()->create();
        $domain = SiteDomain::factory()->alias()->disabled()->create();
        $locale = SiteLocale::factory()->disabled()->create();

        self::assertSame(SiteStatus::Archived, $archivedSingle->status);
        self::assertSame(SiteMode::SingleCategory, $archivedSingle->mode);
        self::assertSame(UserRole::CentralAdmin, $centralAdmin->role);
        self::assertFalse($disabled->isActive());
        self::assertSame(SiteMembershipRole::SiteAdmin, $membership->role);
        self::assertTrue($membership->is_active);
        self::assertSame(SiteDomainType::Alias, $domain->type);
        self::assertFalse($domain->is_active);
        self::assertFalse($locale->is_default);
        self::assertFalse($locale->is_enabled);
    }

    public function test_locale_and_audit_states_remain_valid(): void
    {
        $site = Site::factory()->create();
        $activeLocale = Locale::factory()->active()->create();
        $disabledLocale = Locale::factory()->disabled()->create();
        $centralAudit = AuditLogEntry::factory()->central()->create();
        $siteAudit = AuditLogEntry::factory()->site($site)->create();

        self::assertTrue($activeLocale->is_active);
        self::assertFalse($disabledLocale->is_active);
        self::assertSame('central', $centralAudit->context);
        self::assertNull($centralAudit->site_id);
        self::assertSame('site', $siteAudit->context);
        self::assertTrue($siteAudit->site->is($site));
    }

    public function test_defaults_do_not_collide_and_visual_inputs_do_not_use_faker(): void
    {
        $sites = Site::factory()->count(2)->create();
        $users = User::factory()->count(2)->create();

        self::assertCount(2, $sites->pluck('code')->unique());
        self::assertCount(2, $sites->pluck('domain')->unique());
        self::assertCount(2, $users->pluck('email')->unique());
        self::assertSame(FoundationVisualFixture::site('alpha'), FoundationVisualFixture::site('alpha'));
        self::assertNotSame(FoundationVisualFixture::site('alpha'), FoundationVisualFixture::site('beta'));
        self::assertSame('central-admin@fixture.test', FoundationVisualFixture::centralAdmin()['email']);
    }
}
