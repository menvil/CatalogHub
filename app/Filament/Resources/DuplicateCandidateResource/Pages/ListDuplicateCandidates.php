<?php

namespace App\Filament\Resources\DuplicateCandidateResource\Pages;

use App\Filament\Concerns\ScopesImportBatch;
use App\Filament\Resources\DuplicateCandidateResource;
use Filament\Resources\Pages\ListRecords;

final class ListDuplicateCandidates extends ListRecords
{
    use ScopesImportBatch;

    protected static string $resource = DuplicateCandidateResource::class;
}
