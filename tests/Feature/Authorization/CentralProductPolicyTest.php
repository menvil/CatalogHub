<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CentralProductPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_is_discovered_and_allows_users_with_media_permission(): void
    {
        $product = CentralProduct::factory()->create();

        foreach ([UserRole::CentralAdmin, UserRole::CatalogEditor] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertTrue(Gate::forUser($user)->allows('manageMedia', $product));
        }
    }

    public function test_policy_denies_users_without_media_permission(): void
    {
        $product = CentralProduct::factory()->create();
        $user = User::factory()->create(['role' => UserRole::SiteAdmin]);

        $this->assertFalse(Gate::forUser($user)->allows('manageMedia', $product));
    }
}
