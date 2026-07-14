<?php

namespace App\Filament\Resources\FacetDefinitionResource\Pages;

use App\Filament\Resources\FacetDefinitionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListFacetDefinitions extends ListRecords
{
    protected static string $resource = FacetDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
