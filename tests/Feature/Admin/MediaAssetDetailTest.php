<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaAssetDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_central_admin_to_update_media_source_and_license_fields(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $asset = MediaAsset::factory()->create();

        $this->actingAs($admin)
            ->post(route('central.media.source.update', $asset), [
                'source_url' => 'https://example.com/image.jpg',
                'license_type' => 'manufacturer',
                'license_url' => 'https://example.com/license',
                'attribution' => 'Example Manufacturer',
            ])
            ->assertRedirect(route('central.media.show', $asset));

        $this->assertDatabaseHas('media_sources', [
            'media_asset_id' => $asset->id,
            'source_url' => 'https://example.com/image.jpg',
            'license_type' => 'manufacturer',
        ]);
    }

    public function test_rejects_invalid_source_urls(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $asset = MediaAsset::factory()->create();

        $this->actingAs($admin)
            ->from(route('central.media.show', $asset))
            ->post(route('central.media.source.update', $asset), [
                'source_url' => 'not-a-url',
            ])
            ->assertRedirect(route('central.media.show', $asset))
            ->assertSessionHasErrors('source_url');
    }

    public function test_blocks_media_asset_detail_for_user_without_media_permission(): void
    {
        $user = User::factory()->create(['role' => UserRole::SiteAdmin]);
        $asset = MediaAsset::factory()->create();

        $this->actingAs($user)
            ->get(route('central.media.show', $asset))
            ->assertForbidden();
    }

    public function test_blocks_media_source_update_for_user_without_media_permission(): void
    {
        $user = User::factory()->create(['role' => UserRole::SiteAdmin]);
        $asset = MediaAsset::factory()->create();

        $this->actingAs($user)
            ->post(route('central.media.source.update', $asset), [
                'source_url' => 'https://example.com/image.jpg',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('media_sources', [
            'media_asset_id' => $asset->id,
        ]);
    }
}
