<?php

namespace App\Filament\Resources\SyncConflictResource\Pages;

use App\Filament\Resources\SyncConflictResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewSyncConflict extends ViewRecord
{
    protected static string $resource = SyncConflictResource::class;

    protected function getHeaderActions(): array
    {
        return SyncConflictResource::resolutionActions();
    }
}
