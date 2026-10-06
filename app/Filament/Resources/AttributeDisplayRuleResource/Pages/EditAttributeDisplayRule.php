<?php

namespace App\Filament\Resources\AttributeDisplayRuleResource\Pages;

use App\Actions\CategorySchema\SaveAttributeDisplayRuleAction;
use App\Filament\Resources\AttributeDisplayRuleResource;
use App\Models\AttributeDisplayRule;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditAttributeDisplayRule extends EditRecord
{
    protected static string $resource = AttributeDisplayRuleResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof AttributeDisplayRule) {
            throw new \LogicException('Unexpected display rule owner.');
        }

        return app(SaveAttributeDisplayRuleAction::class)->handle($record, $data, auth()->user());
    }
}
