<?php

namespace App\Filament\Resources\ImportBatchResource\Pages;

use App\Filament\Resources\ImportBatchResource;
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
            $this->relatedAction('rawProducts', 'Raw products', 'raw-products', Heroicon::OutlinedCodeBracket),
            $this->relatedAction('drafts', 'Drafts', 'normalized-product-drafts', Heroicon::OutlinedDocumentCheck),
            $this->relatedAction('errors', 'Errors', 'normalization-errors', Heroicon::OutlinedExclamationTriangle),
            $this->relatedAction('duplicates', 'Duplicates', 'duplicate-candidates', Heroicon::OutlinedSquare2Stack),
        ];
    }

    private function relatedAction(string $name, string $label, string $path, Heroicon $icon): Action
    {
        /** @var ImportBatch $batch */
        $batch = $this->getRecord();

        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->url(url("/admin/{$path}").'?batch='.$batch->id);
    }
}
