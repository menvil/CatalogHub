<?php

namespace App\Jobs\Imports;

use App\Models\Imports\ImportBatch;
use App\Services\Imports\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ProcessImportBatchJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(public int $importBatchId) {}

    public function handle(ImportService $importService): void
    {
        $importService->processQueuedImport(ImportBatch::query()->findOrFail($this->importBatchId));
    }
}
