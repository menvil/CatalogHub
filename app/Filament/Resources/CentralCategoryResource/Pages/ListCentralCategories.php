<?php

namespace App\Filament\Resources\CentralCategoryResource\Pages;

use App\Filament\Resources\CentralCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCentralCategories extends ListRecords
{
    protected static string $resource = CentralCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
