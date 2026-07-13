<?php

namespace Tests\Feature\Imports;

use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\MediaAsset;
use App\Models\MediaSource;
use App\Services\Imports\ImportMediaDownloader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportMediaDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_downloads_image_through_media_service_and_updates_draft_candidates(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('product.jpg', 320, 240);
        Http::fake([
            'https://cdn.example.test/product.jpg' => Http::response(
                file_get_contents($image->getRealPath()),
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
        ]);
        $draft = NormalizedProductDraft::factory()->create([
            'media_json' => [[
                'source_url' => 'https://cdn.example.test/product.jpg',
                'license_type' => 'manufacturer',
                'attribution' => 'Acme',
            ]],
        ]);

        app(ImportMediaDownloader::class)->downloadForDraft($draft);

        $asset = MediaAsset::query()->sole();
        $source = MediaSource::query()->sole();
        $candidate = $draft->fresh()->media_json[0];
        $this->assertSame($asset->id, $candidate['media_asset_id']);
        $this->assertSame('downloaded', $candidate['status']);
        $this->assertSame('https://cdn.example.test/product.jpg', $source->source_url);
        $this->assertSame('manufacturer', $source->license_type);
        $this->assertSame('Acme', $source->attribution);
        Storage::disk($asset->disk)->assertExists($asset->original_path);
        Http::assertSentCount(1);
    }

    public function test_broken_image_creates_error_without_aborting_other_candidates(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('working.jpg', 100, 100);
        Http::fake([
            'https://cdn.example.test/broken.jpg' => Http::response('not found', 404),
            'https://cdn.example.test/working.jpg' => Http::response(
                file_get_contents($image->getRealPath()),
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
        ]);
        $draft = NormalizedProductDraft::factory()->create([
            'media_json' => [
                ['source_url' => 'https://cdn.example.test/broken.jpg'],
                ['source_url' => 'https://cdn.example.test/working.jpg'],
            ],
        ]);

        app(ImportMediaDownloader::class)->downloadForDraft($draft);

        $media = $draft->fresh()->media_json;
        $this->assertSame('failed', $media[0]['status']);
        $this->assertSame('downloaded', $media[1]['status']);
        $this->assertSame(1, MediaAsset::query()->count());
        $this->assertSame('media_download_failed', NormalizationError::query()->sole()->code);
    }
}
