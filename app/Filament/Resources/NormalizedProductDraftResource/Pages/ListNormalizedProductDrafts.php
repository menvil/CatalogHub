<?php

namespace App\Filament\Resources\NormalizedProductDraftResource\Pages;

use App\Filament\Concerns\ScopesImportBatch;
use App\Filament\Resources\NormalizedProductDraftResource;
use Filament\Resources\Pages\ListRecords;

final class ListNormalizedProductDrafts extends ListRecords
{
    use ScopesImportBatch;

    protected static string $resource = NormalizedProductDraftResource::class;
}
