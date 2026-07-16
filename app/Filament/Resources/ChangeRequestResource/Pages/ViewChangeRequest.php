<?php

namespace App\Filament\Resources\ChangeRequestResource\Pages;

use App\Actions\Corrections\ApplyCorrectionToCentralAction;
use App\Actions\Corrections\ApproveCorrectionAction;
use App\Actions\Corrections\RejectCorrectionAction;
use App\Enums\ChangeRequestStatus;
use App\Filament\Resources\ChangeRequestResource;
use App\Models\ChangeRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewChangeRequest extends ViewRecord
{
    protected static string $resource = ChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->changeRequest()->status === ChangeRequestStatus::Pending)
                ->action(function (): void {
                    $user = auth()->user();

                    if ($user instanceof User) {
                        app(ApproveCorrectionAction::class)->handle($user, $this->changeRequest());
                        $this->refreshFormData(['status', 'reviewed_by_user_id', 'reviewed_at']);
                    }
                }),
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon(Heroicon::OutlinedXCircle)
                ->visible(fn (): bool => $this->changeRequest()->status === ChangeRequestStatus::Pending)
                ->schema([
                    Textarea::make('reason')
                        ->label('Rejection reason')
                        ->required()
                        ->maxLength(5000),
                ])
                ->action(function (array $data): void {
                    $user = auth()->user();

                    if ($user instanceof User) {
                        app(RejectCorrectionAction::class)->handle(
                            $user,
                            $this->changeRequest(),
                            (string) $data['reason'],
                        );
                        $this->refreshFormData([
                            'status',
                            'reviewed_by_user_id',
                            'reviewed_at',
                            'rejection_reason',
                        ]);
                    }
                }),
            Action::make('apply')
                ->label('Apply to central')
                ->color('warning')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->changeRequest()->status === ChangeRequestStatus::Approved)
                ->action(function (): void {
                    $user = auth()->user();

                    if ($user instanceof User) {
                        app(ApplyCorrectionToCentralAction::class)->handle($user, $this->changeRequest());
                        $this->refreshFormData([
                            'status',
                            'applied_by_user_id',
                            'applied_at',
                        ]);
                    }
                }),
        ];
    }

    private function changeRequest(): ChangeRequest
    {
        /** @var ChangeRequest $record */
        $record = $this->getRecord();

        return $record;
    }
}
