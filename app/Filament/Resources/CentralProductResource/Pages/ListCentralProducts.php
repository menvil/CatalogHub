<?php

namespace App\Filament\Resources\CentralProductResource\Pages;

use App\Filament\Resources\CentralProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCentralProducts extends ListRecords
{
    protected static string $resource = CentralProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
