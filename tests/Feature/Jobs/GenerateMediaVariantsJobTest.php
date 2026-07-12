<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Media\GenerateMediaVariantsJob;
use App\Models\MediaAsset;
use App\Services\Media\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateMediaVariantsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_generate_media_variants_job(): void
    {
        $asset = MediaAsset::factory()->create();
        $job = new GenerateMediaVariantsJob($asset->id);

        $this->assertSame($asset->id, $job->mediaAssetId);
    }

    public function test_generates_configured_image_variants(): void
    {
        Storage::fake('public');

        $asset = app(MediaService::class)->uploadOriginal(
            UploadedFile::fake()->image('monitor.jpg', 2400, 1600)
        );

        (new GenerateMediaVariantsJob($asset->id))->handle();

        foreach (['thumbnail', 'card', 'gallery', 'hero', 'og'] as $type) {
            $variant = $asset->variants()->where('variant_type', $type)->first();

            $this->assertNotNull($variant, "Missing {$type} variant.");
            $this->assertSame('ready', $variant->status);
            Storage::disk($variant->disk)->assertExists($variant->path);
        }

        $thumbnail = $asset->variants()->where('variant_type', 'thumbnail')->firstOrFail();
        $card = $asset->variants()->where('variant_type', 'card')->firstOrFail();
        $gallery = $asset->variants()->where('variant_type', 'gallery')->firstOrFail();
        $hero = $asset->variants()->where('variant_type', 'hero')->firstOrFail();
        $og = $asset->variants()->where('variant_type', 'og')->firstOrFail();

        $this->assertSame(160, $thumbnail->width);
        $this->assertLessThanOrEqual(640, $card->width);
        $this->assertLessThanOrEqual(1200, $gallery->width);
        $this->assertSame(1600, $hero->width);
        $this->assertSame(1200, $og->width);
        $this->assertSame(630, $og->height);
    }

    public function test_failed_generation_marks_variant_failure_status(): void
    {
        Storage::fake('public');
        $asset = MediaAsset::factory()->create([
            'disk' => 'public',
            'original_path' => 'media/originals/missing.jpg',
        ]);

        (new GenerateMediaVariantsJob($asset->id))->handle();

        $this->assertSame('failed', $asset->variants()->where('variant_type', 'thumbnail')->firstOrFail()->status);
    }
}
