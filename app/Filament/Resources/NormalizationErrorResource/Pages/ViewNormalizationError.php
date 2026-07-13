<?php

namespace App\Filament\Resources\NormalizationErrorResource\Pages;

use App\Filament\Resources\ImportBatchResource;
use App\Filament\Resources\NormalizationErrorResource;
use App\Filament\Resources\NormalizedProductDraftResource;
use App\Filament\Resources\RawProductResource;
use App\Models\Imports\NormalizationError;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewNormalizationError extends ViewRecord
{
    protected static string $resource = NormalizationErrorResource::class;

    protected function getHeaderActions(): array
    {
        /** @var NormalizationError $error */
        $error = $this->getRecord();

        return [
            NormalizationErrorResource::resolveAction(),
            Action::make('batch')
                ->label('Import batch')
                ->icon(Heroicon::OutlinedCircleStack)
                ->url(ImportBatchResource::getUrl('view', ['record' => $error->import_batch_id])),
            Action::make('rawProduct')
                ->label('Raw product')
                ->visible($error->raw_product_id !== null)
                ->url($error->raw_product_id !== null
                    ? RawProductResource::getUrl('view', ['record' => $error->raw_product_id])
                    : null),
            Action::make('draft')
                ->label('Normalized draft')
                ->visible($error->normalized_product_draft_id !== null)
                ->url($error->normalized_product_draft_id !== null
                    ? NormalizedProductDraftResource::getUrl('view', ['record' => $error->normalized_product_draft_id])
                    : null),
        ];
    }
}
