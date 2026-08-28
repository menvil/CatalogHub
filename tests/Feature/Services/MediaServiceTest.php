<?php

namespace Tests\Feature\Services;

use App\Models\MediaAsset;
use App\Services\Media\ImageIngestException;
use App\Services\Media\MediaService;
use App\Services\Media\MediaUploadException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
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

    public function test_rejects_spoofed_upload_without_creating_an_asset_or_file(): void
    {
        Storage::fake('public');

        try {
            app(MediaService::class)->uploadOriginal(UploadedFile::fake()->createWithContent('logo.png', 'not an image'));
            $this->fail('Spoofed raster input must be rejected.');
        } catch (ImageIngestException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(0, MediaAsset::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_stored_normalized_bytes_define_asset_checksum_and_dedupe_leaves_one_file(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('logo.png', 32, 16);

        $first = app(MediaService::class)->uploadOriginal($file);
        $second = app(MediaService::class)->uploadOriginal($file);

        $bytes = Storage::disk('public')->get($first->original_path);
        $this->assertSame('sha256:'.hash('sha256', $bytes), $first->checksum);
        $this->assertSame($first->id, $second->id);
        $this->assertSame([$first->original_path], Storage::disk('public')->allFiles());
    }

    public function test_cleans_up_only_new_file_when_asset_persistence_fails(): void
    {
        Storage::fake('public');
        MediaAsset::creating(static function (): void {
            throw new RuntimeException('persistence failed');
        });

        try {
            app(MediaService::class)->uploadOriginal(UploadedFile::fake()->image('logo.png', 32, 16));
            $this->fail('The persistence exception must remain visible.');
        } catch (MediaUploadException $exception) {
            $this->assertSame('The media upload could not be completed.', $exception->getMessage());
            $this->assertSame('persistence failed', $exception->getPrevious()?->getMessage());
        } finally {
            MediaAsset::flushEventListeners();
            MediaAsset::clearBootedModels();
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, MediaAsset::query()->count());
    }
}
