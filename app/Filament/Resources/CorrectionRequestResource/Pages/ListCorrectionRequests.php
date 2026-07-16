<?php

namespace App\Filament\Resources\CorrectionRequestResource\Pages;

use App\Filament\Resources\CorrectionRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCorrectionRequests extends ListRecords
{
    protected static string $resource = CorrectionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
