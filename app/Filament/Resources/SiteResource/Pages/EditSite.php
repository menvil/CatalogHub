<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

final class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('offerProviderPreview')
                ->label('Price provider preview')
                ->url(fn (): string => OfferProviderPreview::getUrl(['record' => $this->getRecord()])),
        ];
    }
}
