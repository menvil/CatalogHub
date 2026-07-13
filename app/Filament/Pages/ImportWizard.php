<?php

namespace App\Filament\Pages;

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

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor());
    }

    /** @return Collection<int, ImportSource> */
    public function getSources(): Collection
    {
        return ImportSource::query()->where('status', 'active')->orderBy('name')->get();
    }

    public function startImport(): void
    {
        $validated = $this->validate([
            'sourceId' => ['required', 'integer', 'exists:import_sources,id'],
            'artifact' => ['required', 'file', 'max:51200'],
            'locale' => ['nullable', 'string', 'max:32'],
        ]);

        $source = ImportSource::query()
            ->whereKey($validated['sourceId'])
            ->where('status', 'active')
            ->firstOrFail();

        /** @var UploadedFile $artifact */
        $artifact = $validated['artifact'];
        $options = array_filter([
            'locale' => $validated['locale'] ?? null,
        ], static fn (mixed $value): bool => filled($value));

        $batch = app(ImportService::class)->startImport($source, $artifact, $options);
        $this->createdBatchId = $batch->id;

        Notification::make()
            ->title('Import completed')
            ->body("Batch #{$batch->id} was created.")
            ->success()
            ->send();
    }
}
