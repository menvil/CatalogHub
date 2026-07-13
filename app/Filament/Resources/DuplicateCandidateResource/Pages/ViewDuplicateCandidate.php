<?php

namespace App\Filament\Resources\DuplicateCandidateResource\Pages;

use App\Filament\Resources\DuplicateCandidateResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewDuplicateCandidate extends ViewRecord
{
    protected static string $resource = DuplicateCandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DuplicateCandidateResource::reviewAction('markDuplicate', 'Mark duplicate', 'confirmed_duplicate', 'warning'),
            DuplicateCandidateResource::reviewAction('markNotDuplicate', 'Not duplicate', 'not_duplicate', 'gray'),
        ];
    }
}
