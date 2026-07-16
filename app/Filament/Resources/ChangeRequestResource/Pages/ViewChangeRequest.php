<?php

namespace App\Filament\Resources\ChangeRequestResource\Pages;

use App\Actions\Corrections\ApproveCorrectionAction;
use App\Enums\ChangeRequestStatus;
use App\Filament\Resources\ChangeRequestResource;
use App\Models\ChangeRequest;
use App\Models\User;
use Filament\Actions\Action;
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
        ];
    }

    private function changeRequest(): ChangeRequest
    {
        /** @var ChangeRequest $record */
        $record = $this->getRecord();

        return $record;
    }
}
