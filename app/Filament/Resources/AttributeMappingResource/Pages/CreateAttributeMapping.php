<?php

namespace App\Filament\Resources\AttributeMappingResource\Pages;

use App\Actions\Imports\SaveAttributeMappingAction;
use App\Filament\Resources\AttributeMappingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateAttributeMapping extends CreateRecord
{
    protected static string $resource = AttributeMappingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveAttributeMappingAction::class)->handle(null, $data, auth()->user());
    }
}
