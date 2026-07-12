<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_allows_central_admin_to_upload_media_from_library(): void
    {
        Storage::fake('public');

        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($admin)
            ->post(route('central.media.upload'), [
                'file' => UploadedFile::fake()->image('monitor.jpg', 800, 600),
            ])
            ->assertRedirect();

        $asset = MediaAsset::query()->firstOrFail();

        $this->assertSame('monitor.jpg', $asset->original_filename);
        Storage::disk('public')->assertExists($asset->original_path);
    }
}
