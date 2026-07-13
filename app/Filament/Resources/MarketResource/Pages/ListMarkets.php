<?php

namespace App\Filament\Resources\MarketResource\Pages;

use App\Filament\Resources\MarketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListMarkets extends ListRecords
{
    protected static string $resource = MarketResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
