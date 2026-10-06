<?php

namespace App\Filament\Resources\CentralProductResource\Pages;

use App\Actions\CentralCatalog\SaveCentralProductAction;
use App\Filament\Resources\CentralProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateCentralProduct extends CreateRecord
{
    protected static string $resource = CentralProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveCentralProductAction::class)->handle(null, $data, auth()->user());
    }
}
