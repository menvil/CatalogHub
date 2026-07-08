<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_role_is_catalog_editor(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::CatalogEditor, $user->role);
        $this->assertTrue($user->isCatalogEditor());
    }

    public function test_user_can_have_super_admin_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_user_can_have_central_admin_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::CentralAdmin]);

        $this->assertTrue($user->isCentralAdmin());
    }

    public function test_user_can_have_catalog_editor_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->assertTrue($user->isCatalogEditor());
    }

    public function test_user_can_have_site_admin_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::SiteAdmin]);

        $this->assertTrue($user->isSiteAdmin());
    }

    public function test_user_can_have_translator_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::Translator]);

        $this->assertTrue($user->isTranslator());
    }

    public function test_user_can_have_moderator_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::Moderator]);

        $this->assertTrue($user->isModerator());
    }
}
