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

        $this->assertTrue(method_exists($job, 'handle'));
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

        $this->assertSame(160, $asset->variants()->where('variant_type', 'thumbnail')->first()?->width);
        $this->assertLessThanOrEqual(640, $asset->variants()->where('variant_type', 'card')->first()?->width);
        $this->assertLessThanOrEqual(1200, $asset->variants()->where('variant_type', 'gallery')->first()?->width);
        $this->assertSame(1600, $asset->variants()->where('variant_type', 'hero')->first()?->width);
        $this->assertSame(1200, $asset->variants()->where('variant_type', 'og')->first()?->width);
        $this->assertSame(630, $asset->variants()->where('variant_type', 'og')->first()?->height);
    }

    public function test_failed_generation_marks_variant_failure_status(): void
    {
        Storage::fake('public');
        $asset = MediaAsset::factory()->create([
            'disk' => 'public',
            'original_path' => 'media/originals/missing.jpg',
        ]);

        (new GenerateMediaVariantsJob($asset->id))->handle();

        $this->assertSame('failed', $asset->variants()->where('variant_type', 'thumbnail')->first()?->status);
    }
}
