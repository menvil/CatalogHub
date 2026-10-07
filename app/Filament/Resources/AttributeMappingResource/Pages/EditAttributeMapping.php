<?php

namespace App\Filament\Resources\AttributeMappingResource\Pages;

use App\Actions\Imports\SaveAttributeMappingAction;
use App\Filament\Resources\AttributeMappingResource;
use App\Models\Imports\AttributeMapping;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditAttributeMapping extends EditRecord
{
    protected static string $resource = AttributeMappingResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof AttributeMapping) {
            throw new \LogicException('Unexpected record owner.');
        }

        return app(SaveAttributeMappingAction::class)->handle($record, $data, auth()->user());
    }
}
