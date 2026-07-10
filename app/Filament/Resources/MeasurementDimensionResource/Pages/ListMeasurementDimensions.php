<?php

namespace App\Filament\Resources\MeasurementDimensionResource\Pages;

use App\Filament\Resources\MeasurementDimensionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListMeasurementDimensions extends ListRecords
{
    protected static string $resource = MeasurementDimensionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
