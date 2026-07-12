<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_central_admin_to_view_media_library(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        MediaAsset::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get('/central/media')
            ->assertOk()
            ->assertSee('Media Library')
            ->assertSee('assets');
    }

    public function test_blocks_user_without_media_permission(): void
    {
        $user = User::factory()->create(['role' => UserRole::SiteAdmin]);

        $this->actingAs($user)
            ->get('/central/media')
            ->assertForbidden();
    }
}
