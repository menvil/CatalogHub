<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Auth\UpsertSiteMembershipAction;
use App\Enums\SiteMembershipRole;
use App\Enums\UserRole;
use App\Models\AuditLogEntry;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpsertSiteMembershipActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_membership_mutation_has_no_persistence_or_audit_side_effect(): void
    {
        $site = Site::factory()->create();
        $actor = User::factory()->create(['role' => UserRole::SiteAdmin]);
        $member = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        try {
            app(UpsertSiteMembershipAction::class)->handle(
                $actor,
                $member,
                $site,
                SiteMembershipRole::Translator,
                true,
            );
        } finally {
            $this->assertSame(0, SiteMembership::query()->count());
            $this->assertSame(0, AuditLogEntry::query()->count());
        }
    }
}
