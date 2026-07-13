<?php

namespace App\Filament\Pages;

use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\User;
use App\Services\Imports\ImportService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

final class ImportWizard extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Imports';

    protected static ?string $navigationLabel = 'New import';

    protected static ?string $title = 'Import Wizard';

    protected string $view = 'filament.pages.import-wizard';

    public ?int $sourceId = null;

    public mixed $artifact = null;

    public ?string $locale = null;

    public ?int $createdBatchId = null;

    public ?string $batchStatus = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canManageImports();
    }

    /** @return Collection<int, ImportSource> */
    public function getSources(): Collection
    {
        $importService = app(ImportService::class);

        return ImportSource::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->filter(fn (ImportSource $source): bool => $importService->supports($source))
            ->values();
    }

    public function startImport(): void
    {
        if ($this->createdBatchId !== null && $this->batchStatus !== 'failed') {
            return;
        }

        $validated = $this->validate([
            'sourceId' => ['required', 'integer', 'exists:import_sources,id'],
            'artifact' => [
                'required',
                'file',
                'mimetypes:application/octet-stream,application/x-php,text/plain,text/x-php',
                'max:51200',
            ],
            'locale' => ['nullable', 'string', 'max:32'],
        ]);

        $source = ImportSource::query()
            ->whereKey($validated['sourceId'])
            ->where('status', 'active')
            ->firstOrFail();
        $importService = app(ImportService::class);

        if (! $importService->supports($source)) {
            $this->addError('sourceId', 'The selected source does not have a registered importer.');

            return;
        }

        /** @var UploadedFile $artifact */
        $artifact = $validated['artifact'];
        $options = array_filter([
            'locale' => $validated['locale'] ?? null,
        ], static fn (mixed $value): bool => filled($value));

        $shouldQueue = (int) $artifact->getSize()
            > (int) config('imports.queued_artifact_threshold_bytes', 5 * 1024 * 1024);
        $batch = $shouldQueue
            ? $importService->queueImport($source, $artifact, $options)
            : $importService->startImport($source, $artifact, $options);
        $this->createdBatchId = $batch->id;
        $this->batchStatus = $batch->status;

        $isBackground = in_array($batch->status, ['pending', 'processing'], true);

        Notification::make()
            ->title($isBackground ? 'Import queued' : 'Import completed')
            ->body($isBackground
                ? "Batch #{$batch->id} is being processed in the background."
                : "Batch #{$batch->id} was created.")
            ->status($isBackground ? 'info' : 'success')
            ->send();
    }

    public function refreshBatchStatus(): void
    {
        if ($this->createdBatchId === null) {
            return;
        }

        $batch = ImportBatch::query()->find($this->createdBatchId);

        if (! $batch instanceof ImportBatch || $batch->status === $this->batchStatus) {
            return;
        }

        $this->batchStatus = $batch->status;

        if ($batch->status === 'completed') {
            Notification::make()
                ->title('Import completed')
                ->body("Batch #{$batch->id} is ready for review.")
                ->success()
                ->send();
        } elseif ($batch->status === 'failed') {
            Notification::make()
                ->title('Import failed')
                ->body($batch->error_message ?? "Batch #{$batch->id} failed.")
                ->danger()
                ->send();
        }
    }
}
