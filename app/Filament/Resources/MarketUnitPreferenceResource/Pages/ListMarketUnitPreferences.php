<?php

namespace App\Filament\Resources\MarketUnitPreferenceResource\Pages;

use App\Filament\Resources\MarketUnitPreferenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListMarketUnitPreferences extends ListRecords
{
    protected static string $resource = MarketUnitPreferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
