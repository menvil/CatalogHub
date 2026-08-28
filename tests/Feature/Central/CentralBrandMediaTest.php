<?php

namespace Tests\Feature\Central;

use App\Actions\CentralCatalog\SetCentralBrandLogoAction;
use App\Actions\CentralCatalog\UploadCentralBrandLogoAction;
use App\Enums\CentralBrandStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Jobs\Media\GenerateMediaVariantsJob;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\MediaVariant;
use App\Models\User;
use App\Queries\CentralCatalog\CentralBrandQualityQuery;
use App\Services\Audit\AuditRecorder;
use App\Services\Media\MediaResolver;
use App\Services\Media\MediaStorage;
use App\Services\Media\MediaVariantProfile;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\CountryReference;
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

        $assignRoute = app('router')->getRoutes()->getByName('central.brands.media.logo.assign');
        $this->assertNotNull($assignRoute);
        $this->assertContains('can:catalog.brands.manage', $assignRoute->gatherMiddleware());
        $this->assertContains('can:media.manage', $assignRoute->gatherMiddleware());
    }

    public function test_upload_assigns_logo_and_dispatches_exactly_one_brand_logo_profile_job(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->post(route('central.brands.media.logo.store', $brand), ['logo' => UploadedFile::fake()->image('logo.png', 32, 16)])
            ->assertRedirect(route('central.brands.media', $brand))
            ->assertSessionHas('success', 'Brand logo updated.');

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

    public function test_failed_logo_audit_removes_the_new_asset_and_stored_object(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();
        $audit = $this->createMock(AuditRecorder::class);
        $audit->method('record')->willThrowException(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);

        try {
            app(UploadCentralBrandLogoAction::class)(
                User::factory()->create(),
                $brand,
                UploadedFile::fake()->image('logo.png', 32, 16),
            );
            $this->fail('The audit exception must remain visible.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $this->assertSame(0, MediaAsset::query()->count());
        $this->assertSame(0, MediaAssignment::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        Queue::assertNothingPushed();
    }

    public function test_guests_and_translators_cannot_upload_a_brand_logo(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();
        $route = route('central.brands.media.logo.store', $brand);

        $this->post($route, ['logo' => UploadedFile::fake()->image('logo.png', 32, 16)])
            ->assertRedirect(route('filament.central.auth.login'));
        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->post($route, ['logo' => UploadedFile::fake()->image('logo.png', 32, 16)])
            ->assertForbidden();

        $this->assertSame(0, MediaAsset::query()->count());
        $this->assertSame(0, MediaAssignment::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        Queue::assertNothingPushed();
    }

    public function test_guests_and_translators_cannot_remove_a_brand_logo(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();
        $asset = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => 'media/originals/assigned.png']);
        Storage::disk('public')->put($asset->original_path, 'assigned');
        app(SetCentralBrandLogoAction::class)->execute(User::factory()->create(), $brand, $asset);
        $route = route('central.brands.media.logo.destroy', $brand);

        $this->delete($route)->assertRedirect(route('filament.central.auth.login'));
        $this->actingAs(User::factory()->create(['role' => UserRole::Translator]))
            ->delete($route)
            ->assertForbidden();

        $this->assertSame($asset->id, app(MediaResolver::class)->resolve(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id, MediaAssignment::ROLE_BRAND_LOGO)?->id);
        $this->assertDatabaseHas('media_assignments', ['media_asset_id' => $asset->id]);
        Storage::disk('public')->assertExists($asset->original_path);
        Queue::assertNothingPushed();
    }

    public function test_assigned_logo_with_an_unavailable_file_is_not_presented_as_unassigned(): void
    {
        Storage::fake('public');
        $brand = CentralBrand::factory()->create(['name' => 'Samsung']);
        $asset = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => 'media/originals/missing.png']);
        Storage::disk('public')->assertMissing('media/originals/missing.png');
        app(SetCentralBrandLogoAction::class)->execute(User::factory()->create(), $brand, $asset);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(route('central.brands.media', $brand))
            ->assertOk()
            ->assertSee('The assignment exists, but neither a ready semantic variant nor the normalized master can be delivered.')
            ->assertDontSee('No canonical logo assigned');
    }

    public function test_removing_a_missing_canonical_logo_reports_a_no_op(): void
    {
        $brand = CentralBrand::factory()->create();

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->delete(route('central.brands.media.logo.destroy', $brand))
            ->assertRedirect(route('central.brands.media', $brand))
            ->assertSessionHas('warning', 'No canonical Brand logo assignment exists.')
            ->assertSessionMissing('success');

        $this->assertDatabaseCount('media_assignments', 0);
    }

    public function test_replace_and_remove_retain_the_previous_media_asset_and_file(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();
        $user = User::factory()->centralAdmin()->create();
        $first = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => 'media/originals/a.png']);
        Storage::disk('public')->put($first->original_path, 'first');
        app(SetCentralBrandLogoAction::class)->execute(User::factory()->create(), $brand, $first);

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
            ->assertSessionHas('success', 'Brand logo assignment removed.');

        $this->assertNull(app(MediaResolver::class)->resolve(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id, MediaAssignment::ROLE_BRAND_LOGO));
        $this->assertDatabaseHas('media_assets', ['id' => $second->id]);
        Storage::disk('public')->assertExists($second->original_path);
    }

    public function test_existing_compatible_shared_asset_can_be_reused_without_uploading_or_deleting_it(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();
        $asset = MediaAsset::factory()->create([
            'type' => 'image',
            'status' => 'active',
            'mime_type' => 'image/png',
            'disk' => 'public',
            'original_path' => 'media/originals/reusable.png',
        ]);
        Storage::disk('public')->put($asset->original_path, 'reusable');

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->post(route('central.brands.media.logo.assign', $brand), ['media_asset_id' => $asset->id])
            ->assertRedirect(route('central.brands.media', $brand))
            ->assertSessionHas('success', 'Existing media asset assigned as the Brand logo.');

        $this->assertDatabaseCount('media_assets', 1);
        $this->assertDatabaseHas('media_assignments', [
            'media_asset_id' => $asset->id,
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'global',
        ]);
        Storage::disk('public')->assertExists($asset->original_path);
        Queue::assertPushed(GenerateMediaVariantsJob::class, 1);
    }

    public function test_reuse_rejects_incompatible_or_unavailable_assets_and_requires_media_permission(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();
        $failed = MediaAsset::factory()->create(['status' => 'failed', 'mime_type' => 'image/png']);
        $missing = MediaAsset::factory()->create([
            'status' => 'active',
            'mime_type' => 'image/png',
            'disk' => 'public',
            'original_path' => 'media/originals/missing-reuse.png',
        ]);
        $manager = User::factory()->centralAdmin()->create();

        foreach ([$failed, $missing] as $asset) {
            $this->actingAs($manager)
                ->from(route('central.brands.media', $brand))
                ->post(route('central.brands.media.logo.assign', $brand), ['media_asset_id' => $asset->id])
                ->assertRedirect(route('central.brands.media', $brand))
                ->assertSessionHasErrors('media_asset_id');
        }

        config()->set('cataloghub_permissions.roles.catalog_editor', [
            Permission::CentralPanelAccess->value,
            Permission::CentralPageAccess->value,
            Permission::CentralMutationExecute->value,
            Permission::CentralView->value,
            Permission::CatalogBrandsManage->value,
        ]);
        $brandOnlyManager = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $this->actingAs($brandOnlyManager)
            ->get(route('central.brands.media', $brand))
            ->assertOk()
            ->assertSee('Upload a primary logo')
            ->assertDontSee('Reuse an existing MediaAsset');
        $this->actingAs($brandOnlyManager)
            ->post(route('central.brands.media.logo.assign', $brand), ['media_asset_id' => $missing->id])
            ->assertForbidden();

        $this->assertDatabaseCount('media_assignments', 0);
        Queue::assertNothingPushed();
    }

    public function test_extension_is_untrusted_and_valid_content_is_stored_with_its_canonical_format(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();
        $png = UploadedFile::fake()->image('source.png', 20, 10);
        $file = UploadedFile::fake()->createWithContent('misleading.txt', (string) file_get_contents($png->getRealPath()));

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->post(route('central.brands.media.logo.store', $brand), ['logo' => $file])
            ->assertRedirect(route('central.brands.media', $brand));

        $asset = MediaAsset::query()->sole();
        $this->assertSame('image/png', $asset->mime_type);
        $this->assertStringEndsWith('.png', $asset->original_path);
        Storage::disk('public')->assertExists($asset->original_path);
    }

    public function test_storage_failure_keeps_the_previous_assignment_and_reports_a_retryable_error(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->create();
        $old = MediaAsset::factory()->create();
        app(SetCentralBrandLogoAction::class)->execute(User::factory()->create(), $brand, $old);
        $storage = $this->createMock(MediaStorage::class);
        $storage->method('storeNormalized')->willThrowException(new RuntimeException('disk unavailable'));
        $this->app->instance(MediaStorage::class, $storage);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->from(route('central.brands.media', $brand))
            ->post(route('central.brands.media.logo.store', $brand), [
                'logo' => UploadedFile::fake()->image('replacement.png', 20, 10),
            ])
            ->assertRedirect(route('central.brands.media', $brand))
            ->assertSessionHasErrors(['logo' => 'The logo could not be stored. The existing assignment was not changed.']);

        $this->assertDatabaseHas('media_assignments', [
            'entity_id' => $brand->id,
            'media_asset_id' => $old->id,
        ]);
        $this->assertDatabaseCount('media_assets', 1);
        Queue::assertNothingPushed();
    }

    public function test_dispatch_failure_after_commit_is_not_reported_as_an_assignment_rollback(): void
    {
        Storage::fake('public');
        $brand = CentralBrand::factory()->create();
        $old = MediaAsset::factory()->create([
            'disk' => 'public',
            'original_path' => 'media/originals/old.png',
        ]);
        Storage::disk('public')->put($old->original_path, 'old');
        app(SetCentralBrandLogoAction::class)->execute(User::factory()->create(), $brand, $old);

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (object $job): bool => $job instanceof GenerateMediaVariantsJob))
            ->willThrowException(new RuntimeException('queue unavailable'));
        $this->app->instance(Dispatcher::class, $dispatcher);
        $this->withoutExceptionHandling();

        try {
            $this->actingAs(User::factory()->centralAdmin()->create())
                ->post(route('central.brands.media.logo.store', $brand), [
                    'logo' => UploadedFile::fake()->image('replacement.png', 20, 10),
                ]);
            $this->fail('The post-commit dispatch exception must remain visible.');
        } catch (RuntimeException $exception) {
            $this->assertSame('queue unavailable', $exception->getMessage());
        }

        $assignment = MediaAssignment::query()
            ->where('entity_type', MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND)
            ->where('entity_id', $brand->id)
            ->where('role', MediaAssignment::ROLE_BRAND_LOGO)
            ->sole();
        $this->assertNotSame($old->id, $assignment->media_asset_id);
        $this->assertDatabaseHas('media_assets', ['id' => $assignment->media_asset_id]);
    }

    public function test_media_mutations_update_derived_quality_without_changing_lifecycle(): void
    {
        Storage::fake('public');
        Queue::fake();
        $brand = CentralBrand::factory()->active()->create([
            'website_url' => 'https://example.test',
            'country_id' => CountryReference::id('DE'),
            'founded_year' => 1999,
            'support_url' => 'https://example.test/support',
            'contact_email' => null,
            'primary_color' => '#123456',
        ]);
        $quality = app(CentralBrandQualityQuery::class);
        $manager = User::factory()->centralAdmin()->create();

        $this->assertContains('brand_logo_missing', $quality->forBrand($brand)->summary->issueCodes());
        $this->actingAs($manager)->post(route('central.brands.media.logo.store', $brand), [
            'logo' => UploadedFile::fake()->image('logo.png', 20, 10),
        ])->assertRedirect(route('central.brands.media', $brand));
        $this->assertNotContains('brand_logo_missing', $quality->forBrand($brand)->summary->issueCodes());
        $this->assertNotContains('brand_logo_unusable', $quality->forBrand($brand)->summary->issueCodes());

        $this->actingAs($manager)->delete(route('central.brands.media.logo.destroy', $brand))
            ->assertRedirect(route('central.brands.media', $brand));
        $this->assertContains('brand_logo_missing', $quality->forBrand($brand)->summary->issueCodes());
        $this->assertSame(CentralBrandStatus::Active, $brand->fresh()->status);
    }

    public function test_workspace_renders_asset_and_variant_states_without_broken_images(): void
    {
        Storage::fake('public');
        $brand = CentralBrand::factory()->create(['name' => 'Stateful Brand']);
        $asset = MediaAsset::factory()->create([
            'status' => 'processing',
            'disk' => 'public',
            'original_path' => 'media/originals/processing.png',
            'original_filename' => str_repeat('long-', 30).'logo.png',
        ]);
        Storage::disk('public')->put($asset->original_path, 'master');
        MediaVariant::factory()->for($asset, 'asset')->create([
            'variant_type' => 'brand_logo_128',
            'status' => 'processing',
        ]);
        MediaVariant::factory()->for($asset, 'asset')->create([
            'variant_type' => 'brand_logo_256',
            'status' => 'failed',
        ]);
        app(SetCentralBrandLogoAction::class)->execute(User::factory()->create(), $brand, $asset);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(route('central.brands.media', $brand))
            ->assertOk()
            ->assertSee('Brand Media / Identity')
            ->assertSee('Processing')
            ->assertSee('Failed')
            ->assertSee('brand_logo_128')
            ->assertSee('brand_logo_256')
            ->assertSee('data-logo-delivery-state="processing"', false)
            ->assertDontSee('<img', false);
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
