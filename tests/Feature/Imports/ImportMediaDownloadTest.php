<?php

namespace Tests\Feature\Imports;

use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\MediaAsset;
use App\Models\MediaSource;
use App\Services\Imports\ImportMediaDownloader;
use App\Services\Media\MediaService;
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

        $this->downloader()->downloadForDraft($draft);

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

        $this->downloader()->downloadForDraft($draft);

        $media = $draft->fresh()->media_json;
        $this->assertSame('failed', $media[0]['status']);
        $this->assertSame('downloaded', $media[1]['status']);
        $this->assertSame(1, MediaAsset::query()->count());
        $this->assertSame('media_download_failed', NormalizationError::query()->sole()->code);
    }

    public function test_malformed_candidate_creates_error_without_aborting_other_candidates(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('working.jpg', 100, 100);
        Http::fake([
            'https://cdn.example.test/working.jpg' => Http::response(
                file_get_contents($image->getRealPath()),
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
        ]);
        $draft = NormalizedProductDraft::factory()->create([
            'media_json' => [42, ['source_url' => 'https://cdn.example.test/working.jpg']],
        ]);

        $this->downloader()->downloadForDraft($draft);

        $media = $draft->fresh()->media_json;
        $this->assertSame('failed', $media[0]['status']);
        $this->assertSame(42, $media[0]['raw_value']);
        $this->assertSame('downloaded', $media[1]['status']);
        $this->assertSame('media_download_failed', NormalizationError::query()->sole()->code);
    }

    public function test_reimporting_identical_content_reuses_the_asset_source(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('product.jpg', 100, 100);
        Http::fake(fn () => Http::response(
            file_get_contents($image->getRealPath()),
            200,
            ['Content-Type' => 'image/jpeg'],
        ));
        $first = NormalizedProductDraft::factory()->create([
            'media_json' => [['source_url' => 'https://cdn.example.test/product.jpg']],
        ]);
        $second = NormalizedProductDraft::factory()->create([
            'media_json' => [['source_url' => 'https://cdn.example.test/product.jpg']],
        ]);

        $this->downloader()->downloadForDraft($first);
        $this->downloader()->downloadForDraft($second);

        $this->assertSame(1, MediaAsset::query()->count());
        $this->assertSame(1, MediaSource::query()->count());
        $this->assertSame('downloaded', $first->fresh()->media_json[0]['status']);
        $this->assertSame('downloaded', $second->fresh()->media_json[0]['status']);
        $this->assertSame(
            $first->fresh()->media_json[0]['media_asset_id'],
            $second->fresh()->media_json[0]['media_asset_id'],
        );
    }

    public function test_rejects_dns_resolved_private_addresses_before_requesting_media(): void
    {
        Storage::fake('public');
        Http::fake();
        $draft = NormalizedProductDraft::factory()->create([
            'media_json' => [['source_url' => 'https://internal.example.test/product.jpg']],
        ]);
        $downloader = new ImportMediaDownloader(
            app(MediaService::class),
            static fn (string $host): array => ['127.0.0.1'],
        );

        $downloader->downloadForDraft($draft);

        Http::assertNothingSent();
        $this->assertSame('failed', $draft->fresh()->media_json[0]['status']);
        $this->assertStringContainsString(
            'Private or reserved',
            NormalizationError::query()->sole()->message,
        );
    }

    public function test_rejects_oversized_response_during_bounded_download(): void
    {
        Storage::fake('public');
        config()->set('imports.media_download_max_bytes', 8);
        Http::fake([
            'https://cdn.example.test/large.jpg' => Http::response(str_repeat('x', 9), 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);
        $draft = NormalizedProductDraft::factory()->create([
            'media_json' => [['source_url' => 'https://cdn.example.test/large.jpg']],
        ]);

        $this->downloader()->downloadForDraft($draft);

        $this->assertSame('failed', $draft->fresh()->media_json[0]['status']);
        $this->assertStringContainsString('exceeds', NormalizationError::query()->sole()->message);
        $this->assertSame(0, MediaAsset::query()->count());
    }

    public function test_rejects_non_image_bytes_with_an_image_content_type(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://cdn.example.test/spoofed.jpg' => Http::response('plain text payload', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);
        $draft = NormalizedProductDraft::factory()->create([
            'media_json' => [['source_url' => 'https://cdn.example.test/spoofed.jpg']],
        ]);

        $this->downloader()->downloadForDraft($draft);

        $this->assertSame('failed', $draft->fresh()->media_json[0]['status']);
        $this->assertStringContainsString('not an image', NormalizationError::query()->sole()->message);
        $this->assertSame(0, MediaAsset::query()->count());
    }

    private function downloader(): ImportMediaDownloader
    {
        return new ImportMediaDownloader(
            app(MediaService::class),
            static fn (string $host): array => ['93.184.216.34'],
        );
    }
}
