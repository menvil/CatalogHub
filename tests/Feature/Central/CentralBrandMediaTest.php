<?php

namespace Tests\Feature\Central;

use App\Actions\CentralCatalog\SetCentralBrandLogoAction;
use App\Enums\UserRole;
use App\Jobs\Media\GenerateMediaVariantsJob;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\User;
use App\Services\Media\MediaResolver;
use App\Services\Media\MediaVariantProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CentralBrandMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_archived_and_unknown_brand_media_access(): void
    {
        $user = User::factory()->centralAdmin()->create();
        $active = CentralBrand::factory()->active()->create(['name' => 'Samsung']);
        $archived = CentralBrand::factory()->archived()->create();

        $this->actingAs($user)->get(route('central.brands.media', $active))
            ->assertOk()
            ->assertSee('Brand Media')
            ->assertSee('data-screen-id="CA-014"', false)
            ->assertDontSee('>CA-014<', false)
            ->assertDontSee('mx-auto max-w-4xl space-y-admin-section', false);
        $this->get(route('central.brands.media', $archived))->assertOk();
        $this->get(route('central.brands.media', 999999))->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->get(route('central.brands.media', $active))->assertForbidden();
    }

    public function test_upload_assigns_logo_and_dispatches_exactly_one_brand_logo_profile_job(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->post(route('central.brands.media.logo.store', $brand), ['logo' => UploadedFile::fake()->image('logo.png', 32, 16)])
            ->assertRedirect(route('central.brands.media', $brand))
            ->assertSessionHas('status', 'Brand logo updated.');

        $asset = MediaAsset::query()->sole();
        $this->assertDatabaseHas('media_assignments', [
            'media_asset_id' => $asset->id,
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
        ]);
        Storage::disk('public')->assertExists($asset->original_path);
        Queue::assertPushed(GenerateMediaVariantsJob::class, function (GenerateMediaVariantsJob $job) use ($asset): bool {
            return $job->mediaAssetId === $asset->id && $job->profile === MediaVariantProfile::BrandLogo;
        });
        Queue::assertPushed(GenerateMediaVariantsJob::class, 1);
    }

    public function test_invalid_uploads_are_field_errors_without_assets_assignments_or_jobs(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();
        $user = User::factory()->centralAdmin()->create();

        foreach ([
            UploadedFile::fake()->createWithContent('logo.gif', $this->gifBytes()),
            UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
            UploadedFile::fake()->createWithContent('logo.png', 'spoofed image bytes'),
        ] as $file) {
            $this->actingAs($user)->from(route('central.brands.media', $brand))
                ->post(route('central.brands.media.logo.store', $brand), ['logo' => $file])
                ->assertRedirect(route('central.brands.media', $brand))
                ->assertSessionHasErrors('logo');
        }

        $this->assertSame(0, MediaAsset::query()->count());
        $this->assertSame(0, MediaAssignment::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        Queue::assertNothingPushed();
    }

    public function test_replace_and_remove_retain_the_previous_media_asset_and_file(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();
        $user = User::factory()->centralAdmin()->create();
        $first = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => 'media/originals/a.png']);
        Storage::disk('public')->put($first->original_path, 'first');
        app(SetCentralBrandLogoAction::class)->execute($brand, $first);

        $this->actingAs($user)->post(route('central.brands.media.logo.store', $brand), ['logo' => UploadedFile::fake()->image('second.png', 24, 12)])
            ->assertRedirect(route('central.brands.media', $brand));

        $second = MediaAsset::query()->where('id', '!=', $first->id)->sole();
        $this->assertSame($second->id, app(MediaResolver::class)->resolve(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id, MediaAssignment::ROLE_BRAND_LOGO)?->id);
        $this->assertSame(1, MediaAssignment::query()->count());
        $this->assertDatabaseHas('media_assets', ['id' => $first->id]);
        Storage::disk('public')->assertExists($first->original_path);
        Queue::assertPushed(GenerateMediaVariantsJob::class, 1);

        $this->delete(route('central.brands.media.logo.destroy', $brand))
            ->assertRedirect(route('central.brands.media', $brand))
            ->assertSessionHas('status', 'Brand logo removed.');

        $this->assertNull(app(MediaResolver::class)->resolve(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id, MediaAssignment::ROLE_BRAND_LOGO));
        $this->assertDatabaseHas('media_assets', ['id' => $second->id]);
        Storage::disk('public')->assertExists($second->original_path);
    }

    private function gifBytes(): string
    {
        $image = imagecreatetruecolor(2, 2);
        ob_start();
        $ok = imagegif($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        $this->assertTrue($ok);

        return $bytes;
    }
}
