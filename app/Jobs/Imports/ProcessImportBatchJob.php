<?php

namespace App\Jobs\Imports;

use App\Models\Imports\ImportBatch;
use App\Services\Imports\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class ProcessImportBatchJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 75;

    public bool $failOnTimeout = true;

    public int $tries = 1;

    public function __construct(public int $importBatchId) {}

    public function handle(ImportService $importService): void
    {
        $importService->processQueuedImport(ImportBatch::query()->findOrFail($this->importBatchId));
    }

    public function failed(?Throwable $exception): void
    {
        $batch = ImportBatch::query()->find($this->importBatchId);

        if (! $batch instanceof ImportBatch || ! in_array($batch->status, ['pending', 'processing'], true)) {
            return;
        }

        $batch->markFailed($exception?->getMessage() ?: 'The queued import failed.');
    }
}
