<?php

namespace App\Filament\Resources\CatalogSnapshotResource\Pages;

use App\Filament\Pages\SnapshotGenerationPage;
use App\Filament\Resources\CatalogSnapshotResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

final class ListCatalogSnapshots extends ListRecords
{
    protected static string $resource = CatalogSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createSnapshot')
                ->label('Create Snapshot')
                ->icon(Heroicon::OutlinedPlus)
                ->url(SnapshotGenerationPage::getUrl()),
        ];
    }
}
