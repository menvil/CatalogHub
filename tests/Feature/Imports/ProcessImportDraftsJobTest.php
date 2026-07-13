<?php

namespace Tests\Feature\Imports;

use App\Jobs\Imports\ProcessImportDraftsJob;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Imports\RawProduct;
use App\Services\Imports\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class ProcessImportDraftsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_processes_a_bounded_chunk_and_completes_after_the_last_draft(): void
    {
        Queue::fake();
        config()->set('queue.default', 'database');
        config()->set('imports.post_processing_chunk_size', 1);
        $batch = ImportBatch::factory()->create(['status' => 'processing']);
        $first = $this->createDraft($batch, 'First draft');
        $second = $this->createDraft($batch, 'Second draft');
        $service = new ImportService;

        (new ProcessImportDraftsJob($batch->id))->handle($service);

        $this->assertSame('processing', $batch->fresh()->status);
        Queue::assertPushed(
            ProcessImportDraftsJob::class,
            fn (ProcessImportDraftsJob $job): bool => $job->afterDraftId === $first->id,
        );

        (new ProcessImportDraftsJob($batch->id, $first->id))->handle($service);

        $this->assertSame($second->id, $batch->drafts()->max('id'));
        $this->assertSame('completed', $batch->fresh()->status);
    }

    public function test_failed_job_marks_an_incomplete_batch_failed(): void
    {
        $batch = ImportBatch::factory()->create(['status' => 'processing']);

        (new ProcessImportDraftsJob($batch->id))->failed(new RuntimeException('Post-processing timed out'));

        $this->assertSame('failed', $batch->fresh()->status);
        $this->assertSame('Post-processing timed out', $batch->fresh()->error_message);
    }

    public function test_sync_driver_iterates_all_chunks_without_dispatching_recursive_jobs(): void
    {
        Queue::fake();
        config()->set('queue.default', 'sync');
        config()->set('imports.post_processing_chunk_size', 1);
        $batch = ImportBatch::factory()->create(['status' => 'processing']);
        $this->createDraft($batch, 'First draft');
        $this->createDraft($batch, 'Second draft');
        $this->createDraft($batch, 'Third draft');

        (new ProcessImportDraftsJob($batch->id))->handle(new ImportService);

        $this->assertSame('completed', $batch->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_timeout_stays_below_the_default_retry_window(): void
    {
        $job = new ProcessImportDraftsJob(1);

        $this->assertLessThan(config('queue.connections.redis.retry_after'), $job->timeout);
        $this->assertTrue($job->failOnTimeout);
    }

    private function createDraft(ImportBatch $batch, string $title): NormalizedProductDraft
    {
        $rawProduct = RawProduct::factory()->for($batch, 'batch')->create();

        return $batch->drafts()->create([
            'raw_product_id' => $rawProduct->id,
            'title' => $title,
            'normalized_payload_json' => ['title' => $title],
            'attributes_json' => [],
            'media_json' => [],
            'confidence' => '1.0000',
            'status' => 'pending_review',
        ]);
    }
}
