<?php

namespace Tests\Feature\Services;

use App\Models\MediaAsset;
use App\Models\MediaVariant;
use App\Services\Media\BrandLogoPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class BrandLogoPresenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_and_media_use_their_respective_ready_semantic_variants(): void
    {
        [$asset] = $this->assetWithMaster();
        $this->readyVariant($asset, 'brand_logo_128');
        $this->readyVariant($asset, 'brand_logo_256');
        $this->readyVariant($asset, 'brand_logo_512');

        $presenter = app(BrandLogoPresenter::class);

        $this->assertSame('brand_logo_256', $presenter->forDetail($asset)->variantName);
        $this->assertSame('brand_logo_512', $presenter->forMedia($asset)->variantName);
    }

    public function test_skips_processing_failed_and_missing_ready_variants(): void
    {
        [$asset] = $this->assetWithMaster();
        $this->readyVariant($asset, 'brand_logo_512', false);
        $this->variant($asset, 'brand_logo_256', 'failed');
        $this->variant($asset, 'brand_logo_128', 'processing');

        $presentation = app(BrandLogoPresenter::class)->forMedia($asset);

        $this->assertNull($presentation->variantName);
        $this->assertNotNull($presentation->url);
        $this->assertSame($asset->id, $presentation->asset?->id);
    }

    public function test_uses_smaller_ready_variant_before_falling_back_to_master(): void
    {
        [$asset] = $this->assetWithMaster();
        $this->variant($asset, 'brand_logo_512', 'processing');
        $this->readyVariant($asset, 'brand_logo_256');

        $presentation = app(BrandLogoPresenter::class)->forMedia($asset);

        $this->assertSame('brand_logo_256', $presentation->variantName);
        $this->assertNotNull($presentation->url);
    }

    public function test_returns_no_logo_when_no_assignment_asset_or_physical_master_exists(): void
    {
        [$asset] = $this->assetWithMaster(false);
        $presenter = app(BrandLogoPresenter::class);

        $this->assertNull($presenter->forMedia(null)->url);
        $this->assertNull($presenter->forMedia($asset)->url);
        $this->assertNull($presenter->forMedia($asset)->asset);
    }

    /** @return array{MediaAsset, string} */
    private function assetWithMaster(bool $store = true): array
    {
        Storage::fake('public');
        $path = 'media/originals/logo.png';
        $asset = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => $path]);
        if ($store) {
            Storage::disk('public')->put($path, 'master');
        }

        return [$asset, $path];
    }

    private function readyVariant(MediaAsset $asset, string $name, bool $store = true): MediaVariant
    {
        $variant = $this->variant($asset, $name, 'ready');
        if ($store) {
            Storage::disk('public')->put($variant->path, 'variant');
        }

        return $variant;
    }

    private function variant(MediaAsset $asset, string $name, string $status): MediaVariant
    {
        return MediaVariant::factory()->create([
            'media_asset_id' => $asset->id,
            'variant_type' => $name,
            'path' => "media/variants/{$asset->uuid}/{$name}.webp",
            'status' => $status,
        ]);
    }
}
