<?php

namespace App\Filament\Resources\ImportBatchResource\Pages;

use App\Filament\Resources\DuplicateCandidateResource;
use App\Filament\Resources\ImportBatchResource;
use App\Filament\Resources\NormalizationErrorResource;
use App\Filament\Resources\NormalizedProductDraftResource;
use App\Filament\Resources\RawProductResource;
use App\Models\Imports\ImportBatch;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewImportBatch extends ViewRecord
{
    protected static string $resource = ImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->relatedAction('rawProducts', 'Raw products', RawProductResource::class, Heroicon::OutlinedCodeBracket),
            $this->relatedAction('drafts', 'Drafts', NormalizedProductDraftResource::class, Heroicon::OutlinedDocumentCheck),
            $this->relatedAction('errors', 'Errors', NormalizationErrorResource::class, Heroicon::OutlinedExclamationTriangle),
            $this->relatedAction('duplicates', 'Duplicates', DuplicateCandidateResource::class, Heroicon::OutlinedSquare2Stack),
        ];
    }

    /** @param class-string<\Filament\Resources\Resource> $resource */
    private function relatedAction(string $name, string $label, string $resource, Heroicon $icon): Action
    {
        /** @var ImportBatch $batch */
        $batch = $this->getRecord();

        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->url($resource::getUrl(parameters: ['batch' => $batch->id]));
    }
}
