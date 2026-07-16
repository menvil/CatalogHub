<?php

namespace App\Filament\Resources\CatalogSnapshotResource\Pages;

use App\Filament\Resources\CatalogSnapshotResource;
use App\Models\CatalogSnapshot;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

final class RestoreChecklistPage extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CatalogSnapshotResource::class;

    protected static ?string $title = 'Restore Checklist';

    protected string $view = 'filament.resources.catalog-snapshot-resource.pages.restore-checklist';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function snapshot(): CatalogSnapshot
    {
        /** @var CatalogSnapshot $snapshot */
        $snapshot = $this->getRecord();

        return $snapshot;
    }
}
