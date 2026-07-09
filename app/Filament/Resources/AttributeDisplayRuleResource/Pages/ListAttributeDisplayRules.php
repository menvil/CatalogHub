<?php

namespace App\Filament\Resources\AttributeDisplayRuleResource\Pages;

use App\Filament\Resources\AttributeDisplayRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAttributeDisplayRules extends ListRecords
{
    protected static string $resource = AttributeDisplayRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
