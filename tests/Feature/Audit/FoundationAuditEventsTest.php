<?php

namespace Tests\Feature\Audit;

use App\Actions\Auth\AssignUserRoleAction;
use App\Actions\Auth\SetUserDisabledAction;
use App\Actions\Auth\UpsertSiteMembershipAction;
use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\SiteMembershipRole;
use App\Enums\UserRole;
use App\Models\AuditLogEntry;
use App\Models\Site;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class FoundationAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout_are_recorded(): void
    {
        $user = User::factory()->centralAdmin()->create();

        Auth::login($user);
        Auth::logout();

        $this->assertDatabaseHas('audit_log_entries', [
            'actor_id' => $user->getKey(),
            'action' => AuditAction::Login->value,
        ]);
        $this->assertDatabaseHas('audit_log_entries', [
            'actor_id' => $user->getKey(),
            'action' => AuditAction::Logout->value,
        ]);
    }

    public function test_role_assignment_records_whitelisted_before_and_after_data_with_request_id(): void
    {
        request()->headers->set('X-Request-ID', 'request-role-123');
        $actor = User::factory()->centralAdmin()->create();
        $subject = User::factory()->create(['role' => UserRole::CatalogEditor]);

        app(AssignUserRoleAction::class)->handle($actor, $subject, UserRole::CentralAdmin);

        $entry = AuditLogEntry::query()->where('action', AuditAction::RoleAssigned)->sole();
        $this->assertSame(['role' => UserRole::CatalogEditor->value], $entry->before_json);
        $this->assertSame(['role' => UserRole::CentralAdmin->value], $entry->after_json);
        $this->assertSame('request-role-123', $entry->request_id);
    }

    public function test_membership_changes_are_recorded(): void
    {
        $site = Site::factory()->create();
        $actor = User::factory()->siteAdmin($site)->create();
        $member = User::factory()->create(['role' => UserRole::Translator]);

        app(UpsertSiteMembershipAction::class)->handle(
            $actor,
            $member,
            $site,
            SiteMembershipRole::Translator,
            true,
        );
        app(UpsertSiteMembershipAction::class)->handle(
            $actor,
            $member,
            $site,
            SiteMembershipRole::Translator,
            false,
        );

        $entries = AuditLogEntry::query()
            ->where('action', AuditAction::MembershipChanged)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $entries);
        $this->assertNull($entries->first()->before_json);
        $this->assertSame(false, $entries->last()->after_json['is_active']);
    }

    public function test_user_disable_and_enable_are_recorded(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $subject = User::factory()->create();

        app(SetUserDisabledAction::class)->handle($actor, $subject, true);
        app(SetUserDisabledAction::class)->handle($actor, $subject, false);

        $this->assertDatabaseHas('audit_log_entries', ['action' => AuditAction::UserDisabled->value]);
        $this->assertDatabaseHas('audit_log_entries', ['action' => AuditAction::UserEnabled->value]);
        $this->assertTrue($subject->fresh()->isActive());
    }

    public function test_recorder_discards_sensitive_and_unapproved_snapshot_fields(): void
    {
        $actor = User::factory()->centralAdmin()->create();

        $entry = app(AuditRecorder::class)->record(
            AuditAction::RoleAssigned,
            AuditContext::Central,
            $actor,
            $actor,
            null,
            ['role' => 'catalog_editor', 'password' => 'secret'],
            ['role' => 'central_admin', 'api_token' => 'secret'],
        );

        $serialized = json_encode([$entry->before_json, $entry->after_json], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('secret', $serialized);
        $this->assertSame(['role' => 'catalog_editor'], $entry->before_json);
        $this->assertSame(['role' => 'central_admin'], $entry->after_json);
    }
}
