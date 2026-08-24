<?php

namespace Tests\Feature\Services;

use App\Services\Media\MediaService;
use App\Services\Media\MediaVariantGenerator;
use App\Services\Media\MediaVariantProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MediaVariantProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_profile_does_not_create_brand_logo_variants(): void
    {
        Storage::fake('public');
        $asset = app(MediaService::class)->uploadOriginal(UploadedFile::fake()->image('asset.png', 400, 200));
        app(MediaVariantGenerator::class)->generateForAsset($asset->id, MediaVariantProfile::Default);
        $this->assertSame(5, $asset->variants()->count());
        $this->assertDatabaseMissing('media_variants', ['media_asset_id' => $asset->id, 'variant_type' => 'brand_logo_128']);
    }

    public function test_brand_logo_profile_isolated_and_idempotent(): void
    {
        Storage::fake('public');
        $asset = app(MediaService::class)->uploadOriginal(UploadedFile::fake()->image('logo.png', 64, 32));
        $generator = app(MediaVariantGenerator::class);
        $generator->generateForAsset($asset->id, MediaVariantProfile::BrandLogo);
        $generator->generateForAsset($asset->id, MediaVariantProfile::BrandLogo);
        $this->assertSame(3, $asset->variants()->count());
        $this->assertDatabaseMissing('media_variants', ['media_asset_id' => $asset->id, 'variant_type' => 'thumbnail']);
        $this->assertDatabaseHas('media_variants', ['media_asset_id' => $asset->id, 'variant_type' => 'brand_logo_512', 'width' => 64, 'height' => 32]);
    }
}
