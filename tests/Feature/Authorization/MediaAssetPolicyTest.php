<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MediaAssetPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_policy_is_discovered_and_allows_users_with_media_permission(): void
    {
        $asset = MediaAsset::factory()->create();

        foreach ([UserRole::CentralAdmin, UserRole::CatalogEditor] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $gate = Gate::forUser($user);

            $this->assertTrue($gate->allows('viewAny', MediaAsset::class));
            $this->assertTrue($gate->allows('view', $asset));
            $this->assertTrue($gate->allows('create', MediaAsset::class));
            $this->assertTrue($gate->allows('update', $asset));
        }
    }

    public function test_media_policy_denies_users_without_media_permission(): void
    {
        $asset = MediaAsset::factory()->create();
        $user = User::factory()->create(['role' => UserRole::SiteAdmin]);
        $gate = Gate::forUser($user);

        $this->assertFalse($gate->allows('viewAny', MediaAsset::class));
        $this->assertFalse($gate->allows('view', $asset));
        $this->assertFalse($gate->allows('create', MediaAsset::class));
        $this->assertFalse($gate->allows('update', $asset));
    }
}
