<?php

namespace App\Filament\Resources\ContentItemResource\Pages;

use App\Filament\Resources\ContentItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListContentItems extends ListRecords
{
    protected static string $resource = ContentItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
