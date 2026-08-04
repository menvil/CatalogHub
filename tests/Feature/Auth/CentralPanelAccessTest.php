<?php

namespace Tests\Feature\Auth;

use App\Contracts\Auth\CentralAdminAccess;
use App\Enums\UserRole;
use App\Models\User;
use App\Policies\CentralPanelPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CentralPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_access_contract_uses_the_permission_driven_policy(): void
    {
        $this->assertInstanceOf(CentralPanelPolicy::class, app(CentralAdminAccess::class));
    }

    #[DataProvider('roleAccessProvider')]
    public function test_only_central_roles_can_open_the_central_panel(UserRole $role, bool $allowed): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get('/admin/central');

        $allowed ? $response->assertOk() : $response->assertForbidden();
    }

    /**
     * @return array<string, array{UserRole, bool}>
     */
    public static function roleAccessProvider(): array
    {
        return [
            'super admin' => [UserRole::SuperAdmin, true],
            'central admin' => [UserRole::CentralAdmin, true],
            'catalog editor' => [UserRole::CatalogEditor, true],
            'site admin' => [UserRole::SiteAdmin, false],
            'translator' => [UserRole::Translator, true],
            'moderator' => [UserRole::Moderator, false],
        ];
    }
}
