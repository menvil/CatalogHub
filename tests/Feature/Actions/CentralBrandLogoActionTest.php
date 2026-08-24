<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\RemoveCentralBrandLogoAction;
use App\Actions\CentralCatalog\SetCentralBrandLogoAction;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Services\Media\MediaResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CentralBrandLogoActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_replace_and_remove_only_change_assignment(): void
    {
        Storage::fake('public');
        $brand = CentralBrand::factory()->create();
        $first = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => 'media/originals/a.png']);
        $second = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => 'media/originals/b.png']);
        Storage::disk('public')->put($first->original_path, 'a');
        Storage::disk('public')->put($second->original_path, 'b');
        $set = app(SetCentralBrandLogoAction::class);
        $set->execute($brand, $first);
        $set->execute($brand, $second);
        $this->assertSame($second->id, app(MediaResolver::class)->resolve(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id, MediaAssignment::ROLE_BRAND_LOGO)?->id);
        $this->assertSame(1, MediaAssignment::query()->forEntity(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id)->forRole(MediaAssignment::ROLE_BRAND_LOGO)->count());
        $this->assertDatabaseHas('media_assets', ['id' => $first->id]);
        Storage::disk('public')->assertExists($first->original_path);
        app(RemoveCentralBrandLogoAction::class)->__invoke($brand);
        $this->assertNull(app(MediaResolver::class)->resolve(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id, MediaAssignment::ROLE_BRAND_LOGO));
        $this->assertDatabaseHas('media_assets', ['id' => $second->id]);
        Storage::disk('public')->assertExists($second->original_path);
    }
}
