<?php

namespace Tests\Feature\Services;

use App\Enums\MediaDeliveryState;
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
        $this->assertSame(MediaDeliveryState::Ready, $presenter->forMedia($asset)->state);
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

    public function test_distinguishes_missing_assignment_from_unavailable_delivery(): void
    {
        [$asset] = $this->assetWithMaster(false);
        $presenter = app(BrandLogoPresenter::class);

        $missing = $presenter->forMedia(null);
        $unavailable = $presenter->forMedia($asset);

        $this->assertSame(MediaDeliveryState::Missing, $missing->state);
        $this->assertNull($missing->url);
        $this->assertSame(MediaDeliveryState::Unavailable, $unavailable->state);
        $this->assertNull($unavailable->url);
        $this->assertSame($asset->id, $unavailable->asset?->id);
    }

    public function test_processing_and_failed_assets_are_not_delivered_even_when_master_exists(): void
    {
        [$processing] = $this->assetWithMaster();
        $processing->update(['status' => 'processing']);
        $failed = MediaAsset::factory()->create([
            'disk' => 'public',
            'original_path' => 'media/originals/failed.png',
            'status' => 'failed',
        ]);
        Storage::disk('public')->put($failed->original_path, 'master');
        $presenter = app(BrandLogoPresenter::class);

        $this->assertSame(MediaDeliveryState::Processing, $presenter->forMedia($processing)->state);
        $this->assertNull($presenter->forMedia($processing)->url);
        $this->assertSame(MediaDeliveryState::Failed, $presenter->forMedia($failed)->state);
        $this->assertNull($presenter->forMedia($failed)->url);
    }

    public function test_variant_presentations_are_read_only_and_report_missing_ready_files(): void
    {
        [$asset] = $this->assetWithMaster();
        $this->variant($asset, 'brand_logo_128', 'processing');
        $this->variant($asset, 'brand_logo_256', 'failed');
        $this->readyVariant($asset, 'brand_logo_512', false);

        $variants = app(BrandLogoPresenter::class)->variantsForMedia($asset);

        $this->assertSame(['brand_logo_128', 'brand_logo_256', 'brand_logo_512'], array_column($variants, 'name'));
        $this->assertSame([
            MediaDeliveryState::Processing,
            MediaDeliveryState::Failed,
            MediaDeliveryState::Unavailable,
        ], array_column($variants, 'state'));
        $this->assertSame([null, null, null], array_column($variants, 'url'));
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
