<?php

namespace App\Filament\Resources\CentralBrandResource\Pages;

use App\Filament\Resources\CentralBrandResource;
use Filament\Resources\Pages\ListRecords;

final class ListCentralBrands extends ListRecords
{
    protected static string $resource = CentralBrandResource::class;

    public function getSubheading(): string
    {
        return 'Canonical brands used across the central catalog.';
    }

    public function hasActiveBrandTableConstraints(): bool
    {
        return $this->hasTableSearch()
            || filled($this->getTableFilterState('status')['value'] ?? null);
    }
}
