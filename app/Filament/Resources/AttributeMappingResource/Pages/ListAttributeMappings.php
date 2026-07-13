<?php

namespace App\Filament\Resources\AttributeMappingResource\Pages;

use App\Filament\Resources\AttributeMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAttributeMappings extends ListRecords
{
    protected static string $resource = AttributeMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
