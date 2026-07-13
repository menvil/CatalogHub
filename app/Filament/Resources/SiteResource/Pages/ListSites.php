<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Pages\CreateSiteWizard;
use App\Filament\Resources\SiteResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

final class ListSites extends ListRecords
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('create')->label('Create site')->url(CreateSiteWizard::getUrl())];
    }
}
