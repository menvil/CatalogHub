<?php

namespace App\Filament\Resources\AttributeDisplayRuleResource\Pages;

use App\Actions\CategorySchema\SaveAttributeDisplayRuleAction;
use App\Filament\Resources\AttributeDisplayRuleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateAttributeDisplayRule extends CreateRecord
{
    protected static string $resource = AttributeDisplayRuleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveAttributeDisplayRuleAction::class)->handle(null, $data, auth()->user());
    }
}
