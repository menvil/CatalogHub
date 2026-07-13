<?php

namespace App\Filament\Resources\NormalizedProductDraftResource\Pages;

use App\Actions\Imports\ApproveNormalizedProductDraftAction;
use App\Actions\Imports\RejectNormalizedProductDraftAction;
use App\Filament\Resources\ImportBatchResource;
use App\Filament\Resources\NormalizedProductDraftResource;
use App\Filament\Resources\RawProductResource;
use App\Models\Imports\NormalizedProductDraft;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewNormalizedProductDraft extends ViewRecord
{
    protected static string $resource = NormalizedProductDraftResource::class;

    protected function getHeaderActions(): array
    {
        /** @var NormalizedProductDraft $draft */
        $draft = $this->getRecord();

        return [
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === 'pending_review')
                ->action(fn (NormalizedProductDraft $record): NormalizedProductDraft => app(ApproveNormalizedProductDraftAction::class)
                    ->handle($record, auth()->user())),
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon(Heroicon::OutlinedXCircle)
                ->visible(fn (): bool => $this->getRecord()->status === 'pending_review')
                ->schema([
                    Textarea::make('reason')
                        ->label('Rejection reason')
                        ->required()
                        ->maxLength(2000),
                ])
                ->action(fn (array $data, NormalizedProductDraft $record): NormalizedProductDraft => app(RejectNormalizedProductDraftAction::class)
                    ->handle($record, auth()->user(), (string) $data['reason'])),
            Action::make('rawProduct')
                ->label('Raw product')
                ->icon(Heroicon::OutlinedCodeBracket)
                ->url(RawProductResource::getUrl('view', ['record' => $draft->raw_product_id])),
            Action::make('batch')
                ->label('Import batch')
                ->icon(Heroicon::OutlinedCircleStack)
                ->url(ImportBatchResource::getUrl('view', ['record' => $draft->import_batch_id])),
        ];
    }
}
