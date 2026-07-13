<?php

namespace Tests\Feature\Imports;

use App\Jobs\Imports\ProcessImportBatchJob;
use App\Models\Imports\ImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProcessImportBatchJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeout_stays_below_the_default_retry_window_and_fails_on_timeout(): void
    {
        $job = new ProcessImportBatchJob(1);

        $this->assertLessThan(config('queue.connections.redis.retry_after'), $job->timeout);
        $this->assertTrue($job->failOnTimeout);
    }

    public function test_failed_job_marks_an_incomplete_batch_failed(): void
    {
        $batch = ImportBatch::factory()->create(['status' => 'processing']);

        (new ProcessImportBatchJob($batch->id))->failed(new RuntimeException('Worker timed out'));

        $batch->refresh();

        $this->assertSame('failed', $batch->status);
        $this->assertSame('Worker timed out', $batch->error_message);
        $this->assertNotNull($batch->finished_at);
    }

    public function test_failed_callback_does_not_overwrite_a_completed_batch(): void
    {
        $batch = ImportBatch::factory()->create([
            'status' => 'completed',
            'error_message' => null,
        ]);

        (new ProcessImportBatchJob($batch->id))->failed(new RuntimeException('Late failure callback'));

        $batch->refresh();

        $this->assertSame('completed', $batch->status);
        $this->assertNull($batch->error_message);
    }
}
