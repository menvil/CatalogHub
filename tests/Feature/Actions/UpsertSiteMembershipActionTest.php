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
use App\Services\Audit\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
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

    public function test_membership_write_rolls_back_when_the_in_transaction_audit_write_fails(): void
    {
        $site = Site::factory()->create();
        $actor = User::factory()->siteAdmin($site)->create();
        $member = User::factory()->create();
        $membership = SiteMembership::factory()->for($member)->for($site)->create([
            'role' => SiteMembershipRole::Translator,
            'is_active' => true,
        ]);
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->andThrow(new \RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);

        try {
            app(UpsertSiteMembershipAction::class)->handle(
                $actor,
                $member,
                $site,
                SiteMembershipRole::SiteAdmin,
                false,
            );
            $this->fail('Expected audit failure to escape the transaction.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $membership->refresh();
        $this->assertSame(SiteMembershipRole::Translator, $membership->role);
        $this->assertTrue($membership->is_active);
    }
}
