<?php

namespace App\Jobs\Imports;

use App\Models\Imports\ImportBatch;
use App\Services\Imports\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class ProcessImportDraftsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 75;

    public bool $failOnTimeout = true;

    public int $tries = 1;

    public function __construct(
        public int $importBatchId,
        public int $afterDraftId = 0,
        public ?int $draftId = null,
        public int $mediaOffset = 0,
    ) {}

    public function handle(ImportService $importService): void
    {
        $batch = ImportBatch::query()->findOrFail($this->importBatchId);

        if (config('queue.default') === 'sync') {
            $cursor = [
                'after_draft_id' => $this->afterDraftId,
                'draft_id' => $this->draftId,
                'media_offset' => $this->mediaOffset,
            ];

            while (($cursor = $importService->processDraftChunk(
                $batch,
                $cursor['after_draft_id'],
                $cursor['draft_id'],
                $cursor['media_offset'],
            )) !== null) {
                // The sync driver has no worker boundary, so iterate without growing the call stack.
            }

            return;
        }

        $cursor = $importService->processDraftChunk(
            $batch,
            $this->afterDraftId,
            $this->draftId,
            $this->mediaOffset,
        );

        if ($cursor !== null) {
            self::dispatch(
                $batch->id,
                $cursor['after_draft_id'],
                $cursor['draft_id'],
                $cursor['media_offset'],
            )->afterCommit();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $batch = ImportBatch::query()->find($this->importBatchId);

        if (! $batch instanceof ImportBatch || $batch->status !== 'processing') {
            return;
        }

        $batch->markFailed($exception?->getMessage() ?: 'Import draft post-processing failed.');
    }
}
