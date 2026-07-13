<?php

namespace Tests\Unit\Models;

use App\Models\Imports\ImportArtifact;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Imports\RawProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_metadata_counters_and_lifecycle_datetimes(): void
    {
        $batch = ImportBatch::factory()->create([
            'metadata_json' => ['requested_by' => 'test'],
            'raw_items_count' => 3,
            'started_at' => now(),
            'finished_at' => now(),
        ])->fresh();

        $this->assertSame(['requested_by' => 'test'], $batch->metadata_json);
        $this->assertSame(3, $batch->raw_items_count);
        $this->assertNotNull($batch->started_at);
        $this->assertNotNull($batch->finished_at);
    }

    public function test_exposes_all_pipeline_relationships(): void
    {
        $batch = ImportBatch::factory()->create();

        $this->assertInstanceOf(ImportSource::class, $batch->source()->getRelated());
        $this->assertInstanceOf(ImportArtifact::class, $batch->artifacts()->getRelated());
        $this->assertInstanceOf(RawProduct::class, $batch->rawProducts()->getRelated());
        $this->assertInstanceOf(NormalizedProductDraft::class, $batch->drafts()->getRelated());
        $this->assertInstanceOf(NormalizationError::class, $batch->errors()->getRelated());
    }

    public function test_updates_batch_lifecycle_through_helpers(): void
    {
        $batch = ImportBatch::factory()->create();

        $batch->markStarted();
        $this->assertSame('processing', $batch->status);
        $this->assertNotNull($batch->started_at);

        $batch->markFinished();
        $this->assertSame('completed', $batch->status);
        $this->assertNotNull($batch->finished_at);

        $batch->markFailed('Invalid payload');
        $this->assertSame('failed', $batch->status);
        $this->assertSame('Invalid payload', $batch->error_message);
        $this->assertNotNull($batch->finished_at);
    }

    public function test_factory_filename_is_fully_optional(): void
    {
        $filenames = ImportBatch::factory()->count(30)->make()->pluck('original_filename');

        $this->assertTrue($filenames->every(
            fn (?string $filename): bool => $filename === null || str_ends_with($filename, '.data'),
        ));
    }
}
