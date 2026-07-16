<?php

namespace App\Filament\Resources\CatalogSnapshotResource\Pages;

use App\Filament\Resources\CatalogSnapshotResource;
use App\Models\CatalogSnapshot;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewCatalogSnapshot extends ViewRecord
{
    protected static string $resource = CatalogSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        /** @var CatalogSnapshot $snapshot */
        $snapshot = $this->getRecord();

        return [
            Action::make('restoreChecklist')
                ->label('Restore checklist')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->url(CatalogSnapshotResource::getUrl('restore-checklist', ['record' => $snapshot])),
            CatalogSnapshotResource::downloadAction(),
        ];
    }
}
