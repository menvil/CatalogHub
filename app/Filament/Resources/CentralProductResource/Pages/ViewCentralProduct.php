<?php

namespace App\Filament\Resources\CentralProductResource\Pages;

use App\Filament\Resources\CentralProductResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewCentralProduct extends ViewRecord
{
    protected static string $resource = CentralProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('specs')
                ->label('Specs')
                ->icon(Heroicon::OutlinedListBullet)
                ->url(fn (): string => ProductSpecsEditor::getUrl(['record' => $this->getRecord()])),
            EditAction::make(),
        ];
    }
}
