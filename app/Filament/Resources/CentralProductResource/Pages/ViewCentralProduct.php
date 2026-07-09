<?php

namespace App\Filament\Resources\CentralProductResource\Pages;

use App\Filament\Resources\CentralProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewCentralProduct extends ViewRecord
{
    protected static string $resource = CentralProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
