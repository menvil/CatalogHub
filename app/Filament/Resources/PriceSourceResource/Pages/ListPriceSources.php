<?php

namespace App\Filament\Resources\PriceSourceResource\Pages;

use App\Filament\Resources\PriceSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListPriceSources extends ListRecords
{
    protected static string $resource = PriceSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
