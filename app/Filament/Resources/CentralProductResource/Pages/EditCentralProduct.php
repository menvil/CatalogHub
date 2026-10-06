<?php

namespace App\Filament\Resources\CentralProductResource\Pages;

use App\Actions\CentralCatalog\SaveCentralProductAction;
use App\Filament\Resources\CentralProductResource;
use App\Models\CentralCatalog\CentralProduct;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditCentralProduct extends EditRecord
{
    protected static string $resource = CentralProductResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof CentralProduct) {
            throw new \LogicException('Unexpected Product owner.');
        }

        return app(SaveCentralProductAction::class)->handle($record, $data, auth()->user());
    }
}
