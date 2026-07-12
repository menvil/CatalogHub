<?php

namespace Tests\Feature\Services;

use App\Models\MediaAsset;
use App\Services\Media\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class MediaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_media_service_from_container(): void
    {
        $this->assertInstanceOf(MediaService::class, app(MediaService::class));
    }

    public function test_uploads_original_image_and_creates_media_asset(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('monitor.jpg', 1200, 800);

        $asset = app(MediaService::class)->uploadOriginal($file, ['type' => 'image']);

        $this->assertInstanceOf(MediaAsset::class, $asset);
        $this->assertTrue($asset->exists);
        $this->assertSame('public', $asset->disk);
        $this->assertNotSame('', $asset->original_path);
        $this->assertSame('monitor.jpg', $asset->original_filename);
        $this->assertSame(1200, $asset->width);
        $this->assertSame(800, $asset->height);
        $this->assertStringStartsWith('image/', (string) $asset->mime_type);
        $this->assertGreaterThan(0, $asset->file_size);
        $this->assertStringStartsWith('sha256:', (string) $asset->checksum);
        Storage::disk($asset->disk)->assertExists($asset->original_path);
    }

    public function test_does_not_create_duplicate_asset_for_same_checksum(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('monitor.jpg', 400, 300);

        $first = app(MediaService::class)->uploadOriginal($file);
        $second = app(MediaService::class)->uploadOriginal($file);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, MediaAsset::query()->count());
    }

    public function test_rejects_image_mimes_that_variant_job_cannot_decode(): void
    {
        Storage::fake('public');

        $this->assertSame(0, MediaAsset::query()->count());
        $this->expectException(InvalidArgumentException::class);

        app(MediaService::class)->uploadOriginal(
            UploadedFile::fake()->create('icon.svg', 1, 'image/svg+xml')
        );
    }
}
