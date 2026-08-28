<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\RemoveCentralBrandLogoAction;
use App\Actions\CentralCatalog\SetCentralBrandLogoAction;
use App\Enums\AuditAction;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Media\MediaResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

final class CentralBrandLogoActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_replace_and_remove_only_change_assignment(): void
    {
        Storage::fake('public');
        $brand = CentralBrand::factory()->create();
        $otherBrand = CentralBrand::factory()->create();
        $first = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => 'media/originals/a.png']);
        $second = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => 'media/originals/b.png']);
        Storage::disk('public')->put($first->original_path, 'a');
        Storage::disk('public')->put($second->original_path, 'b');
        $set = app(SetCentralBrandLogoAction::class);
        $set->execute(User::factory()->create(), $brand, $first);
        $set->execute(User::factory()->create(), $brand, $second);
        $set->execute(User::factory()->create(), $otherBrand, $first);
        $this->assertSame($second->id, app(MediaResolver::class)->resolve(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id, MediaAssignment::ROLE_BRAND_LOGO)?->id);
        $this->assertSame(1, MediaAssignment::query()->forEntity(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id)->forRole(MediaAssignment::ROLE_BRAND_LOGO)->count());
        $this->assertDatabaseHas('media_assets', ['id' => $first->id]);
        Storage::disk('public')->assertExists($first->original_path);
        app(RemoveCentralBrandLogoAction::class)->__invoke(User::factory()->create(), $brand);
        app(RemoveCentralBrandLogoAction::class)->__invoke(User::factory()->create(), $brand);
        $this->assertNull(app(MediaResolver::class)->resolve(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id, MediaAssignment::ROLE_BRAND_LOGO));
        $this->assertDatabaseHas('media_assets', ['id' => $second->id]);
        Storage::disk('public')->assertExists($second->original_path);
        $this->assertSame($first->id, app(MediaResolver::class)->resolve(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $otherBrand->id, MediaAssignment::ROLE_BRAND_LOGO)?->id);
    }

    public function test_assignment_is_exactly_global_primary_and_does_not_touch_other_contexts(): void
    {
        $brand = CentralBrand::factory()->create();
        $asset = MediaAsset::factory()->create();
        $localized = MediaAssignment::factory()->create([
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'locale' => 'de-DE',
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'global',
        ]);

        $result = app(SetCentralBrandLogoAction::class)->execute(User::factory()->create(), $brand, $asset);

        $this->assertTrue($result->changed);
        $this->assertSame($asset->id, $result->assignment->media_asset_id);
        $this->assertDatabaseHas('media_assignments', [
            'id' => $result->assignment->id,
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'global',
        ]);
        $this->assertDatabaseHas('media_assignments', ['id' => $localized->id, 'locale' => 'de-DE']);
    }

    public function test_same_asset_is_a_no_op_without_audit_noise(): void
    {
        $brand = CentralBrand::factory()->create();
        $asset = MediaAsset::factory()->create();
        $actor = User::factory()->create();
        $set = app(SetCentralBrandLogoAction::class);

        $first = $set->execute($actor, $brand, $asset);
        $second = $set->execute($actor, $brand, $asset);

        $this->assertTrue($first->changed);
        $this->assertFalse($second->changed);
        $this->assertSame($first->assignment->id, $second->assignment->id);
        $this->assertDatabaseCount('media_assignments', 1);
        $this->assertDatabaseCount('audit_log_entries', 1);
    }

    public function test_set_repairs_the_existing_primary_context_instead_of_competing_with_it(): void
    {
        $brand = CentralBrand::factory()->create();
        $oldAsset = MediaAsset::factory()->create();
        $newAsset = MediaAsset::factory()->create();
        $existing = MediaAssignment::factory()->create([
            'media_asset_id' => $oldAsset->id,
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'position' => 9,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'private',
        ]);

        $result = app(SetCentralBrandLogoAction::class)->execute(User::factory()->create(), $brand, $newAsset);

        $this->assertTrue($result->changed);
        $this->assertSame($existing->id, $result->assignment->id);
        $this->assertDatabaseCount('media_assignments', 1);
        $this->assertDatabaseHas('media_assignments', [
            'id' => $existing->id,
            'media_asset_id' => $newAsset->id,
            'position' => 0,
            'visibility' => 'global',
            'is_primary' => true,
        ]);
    }

    public function test_same_asset_non_canonical_repair_has_a_meaningful_audit_snapshot(): void
    {
        $brand = CentralBrand::factory()->create();
        $asset = MediaAsset::factory()->create();
        $existing = MediaAssignment::factory()->create([
            'media_asset_id' => $asset->id,
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'position' => 9,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'private',
        ]);

        $result = app(SetCentralBrandLogoAction::class)->execute(User::factory()->create(), $brand, $asset);

        $this->assertTrue($result->changed);
        $this->assertSame($existing->id, $result->assignment->id);
        $this->assertDatabaseHas('media_assignments', [
            'id' => $existing->id,
            'media_asset_id' => $asset->id,
            'position' => 0,
            'visibility' => 'global',
        ]);
        $entry = AuditLogEntry::query()->sole();
        $this->assertSame([
            'media_asset_id' => null,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
        ], $entry->before_json);
        $this->assertSame([
            'media_asset_id' => $asset->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
        ], $entry->after_json);
    }

    public function test_logo_audit_is_semantic_and_minimal(): void
    {
        $brand = CentralBrand::factory()->create();
        $asset = MediaAsset::factory()->create();
        $actor = User::factory()->create();

        app(SetCentralBrandLogoAction::class)->execute($actor, $brand, $asset);
        app(RemoveCentralBrandLogoAction::class)($actor, $brand);

        $entries = AuditLogEntry::query()->orderBy('id')->get();
        $this->assertSame(AuditAction::CatalogBrandLogoAssigned->value, $entries[0]->action);
        $this->assertSame([
            'media_asset_id' => $asset->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
        ], $entries[0]->after_json);
        $this->assertSame(AuditAction::CatalogBrandLogoRemoved->value, $entries[1]->action);
        $this->assertSame([
            'media_asset_id' => $asset->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
        ], $entries[1]->before_json);
    }

    public function test_audit_failure_rolls_back_replace_and_remove(): void
    {
        $brand = CentralBrand::factory()->create();
        $first = MediaAsset::factory()->create();
        $second = MediaAsset::factory()->create();
        $actor = User::factory()->create();
        app(SetCentralBrandLogoAction::class)->execute($actor, $brand, $first);

        $audit = $this->createMock(AuditRecorder::class);
        $audit->method('record')->willThrowException(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);

        foreach ([
            fn () => app(SetCentralBrandLogoAction::class)->execute($actor, $brand, $second),
            fn () => app(RemoveCentralBrandLogoAction::class)($actor, $brand),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Expected audit failure.');
            } catch (RuntimeException $exception) {
                $this->assertSame('audit unavailable', $exception->getMessage());
            }

            $this->assertDatabaseHas('media_assignments', [
                'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
                'entity_id' => $brand->id,
                'role' => MediaAssignment::ROLE_BRAND_LOGO,
                'media_asset_id' => $first->id,
                'is_primary' => true,
                'visibility' => 'global',
            ]);
        }
    }
}
