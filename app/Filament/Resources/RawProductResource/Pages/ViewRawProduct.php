<?php

namespace App\Filament\Resources\RawProductResource\Pages;

use App\Filament\Resources\ImportBatchResource;
use App\Filament\Resources\NormalizedProductDraftResource;
use App\Filament\Resources\RawProductResource;
use App\Models\Imports\RawProduct;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewRawProduct extends ViewRecord
{
    protected static string $resource = RawProductResource::class;

    protected function getHeaderActions(): array
    {
        /** @var RawProduct $rawProduct */
        $rawProduct = $this->getRecord();
        $draft = $rawProduct->draft()->first();

        return [
            Action::make('batch')
                ->label('Import batch')
                ->icon(Heroicon::OutlinedCircleStack)
                ->url(ImportBatchResource::getUrl('view', ['record' => $rawProduct->import_batch_id])),
            Action::make('draft')
                ->label('Normalized draft')
                ->icon(Heroicon::OutlinedDocumentCheck)
                ->visible($draft !== null)
                ->url($draft !== null
                    ? NormalizedProductDraftResource::getUrl('view', ['record' => $draft])
                    : null),
        ];
    }
}
