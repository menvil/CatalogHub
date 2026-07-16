<?php

namespace App\Filament\Resources\CorrectionRequestResource\Pages;

use App\Actions\Corrections\CreateCorrectionRequestAction;
use App\Filament\Resources\CorrectionRequestResource;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class CreateCorrectionRequest extends CreateRecord
{
    protected static string $resource = CorrectionRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new LogicException('An authenticated user is required.');
        }

        $product = CentralProduct::query()->findOrFail($data['central_product_id']);

        return app(CreateCorrectionRequestAction::class)->handle(
            creator: $user,
            product: $product,
            fieldPath: (string) $data['field_path'],
            proposedValue: $data['proposed_value'],
            evidenceUrl: $data['evidence_url'] ?? null,
            evidenceNote: $data['evidence_note'] ?? null,
        );
    }
}
