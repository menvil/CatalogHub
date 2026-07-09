<?php

namespace App\Filament\Resources\CentralBrandResource\Pages;

use App\Filament\Resources\CentralBrandResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCentralBrands extends ListRecords
{
    protected static string $resource = CentralBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
