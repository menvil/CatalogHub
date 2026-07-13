<?php

namespace Tests\Feature\Imports;

use App\Jobs\Imports\ProcessImportDraftsJob;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\DuplicateCandidate;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Imports\RawProduct;
use App\Services\Imports\DuplicateDetector;
use App\Services\Imports\ImportMediaDownloader;
use App\Services\Imports\ImportService;
use App\Services\Media\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProcessImportDraftsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_processes_a_bounded_chunk_and_completes_after_the_last_draft(): void
    {
        Queue::fake();
        config()->set('queue.default', 'database');
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
        $batch = ImportBatch::factory()->create(['status' => 'processing']);
        $this->createDraft($batch, 'First draft');
        $this->createDraft($batch, 'Second draft');
        $this->createDraft($batch, 'Third draft');

        (new ProcessImportDraftsJob($batch->id))->handle(new ImportService);

        $this->assertSame('completed', $batch->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_revisits_a_draft_for_each_media_chunk_before_detecting_duplicates(): void
    {
        Storage::fake('public');
        Queue::fake();
        config()->set('queue.default', 'database');
        $image = UploadedFile::fake()->image('product.jpg', 100, 100);
        Http::fake(fn () => Http::response(
            file_get_contents($image->getRealPath()),
            200,
            ['Content-Type' => 'image/jpeg'],
        ));
        CentralProduct::factory()->create(['name' => 'Imported Product']);
        $batch = ImportBatch::factory()->create(['status' => 'processing']);
        $draft = $this->createDraft($batch, 'Imported Product', [
            ['source_url' => 'https://cdn.example.test/first.jpg'],
            ['source_url' => 'https://cdn.example.test/second.jpg'],
        ]);
        $service = new ImportService(
            mediaDownloader: new ImportMediaDownloader(
                app(MediaService::class),
                static fn (string $host): array => ['93.184.216.34'],
            ),
            duplicateDetector: new DuplicateDetector,
        );

        (new ProcessImportDraftsJob($batch->id))->handle($service);

        Http::assertSentCount(1);
        $this->assertSame('downloaded', $draft->fresh()->media_json[0]['status']);
        $this->assertArrayNotHasKey('status', $draft->fresh()->media_json[1]);
        $this->assertSame(0, DuplicateCandidate::query()->count());
        Queue::assertPushed(
            ProcessImportDraftsJob::class,
            fn (ProcessImportDraftsJob $job): bool => $job->afterDraftId === 0
                && $job->draftId === $draft->id
                && $job->mediaOffset === 1,
        );

        (new ProcessImportDraftsJob($batch->id, 0, $draft->id, 1))->handle($service);

        Http::assertSentCount(2);
        $this->assertSame('downloaded', $draft->fresh()->media_json[1]['status']);
        $this->assertSame($draft->id, DuplicateCandidate::query()->sole()->normalized_product_draft_id);
        $this->assertSame('completed', $batch->fresh()->status);
    }

    public function test_missing_cursor_draft_falls_back_to_the_next_draft(): void
    {
        Queue::fake();
        config()->set('queue.default', 'database');
        $batch = ImportBatch::factory()->create(['status' => 'processing']);
        $deleted = $this->createDraft($batch, 'Deleted draft');
        $remaining = $this->createDraft($batch, 'Remaining draft');
        $centralProduct = CentralProduct::factory()->create(['name' => 'Remaining draft']);
        $deletedId = $deleted->id;
        $deleted->delete();

        (new ProcessImportDraftsJob($batch->id, 0, $deletedId, 5))->handle(new ImportService(
            duplicateDetector: new DuplicateDetector,
        ));

        $this->assertDatabaseHas('duplicate_candidates', [
            'normalized_product_draft_id' => $remaining->id,
            'candidate_id' => $centralProduct->id,
        ]);
        $this->assertSame('completed', $batch->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_timeout_stays_below_the_default_retry_window(): void
    {
        $job = new ProcessImportDraftsJob(1);

        $this->assertLessThan(config('queue.connections.redis.retry_after'), $job->timeout);
        $this->assertTrue($job->failOnTimeout);
    }

    /** @param list<array<string, mixed>> $media */
    private function createDraft(
        ImportBatch $batch,
        string $title,
        array $media = [],
    ): NormalizedProductDraft {
        $rawProduct = RawProduct::factory()->for($batch, 'batch')->create();

        return $batch->drafts()->create([
            'raw_product_id' => $rawProduct->id,
            'title' => $title,
            'normalized_payload_json' => ['title' => $title],
            'attributes_json' => [],
            'media_json' => $media,
            'confidence' => '1.0000',
            'status' => 'pending_review',
        ]);
    }
}
