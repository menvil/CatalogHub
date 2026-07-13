<?php

namespace App\Filament\Resources\NormalizationErrorResource\Pages;

use App\Filament\Concerns\ScopesImportBatch;
use App\Filament\Resources\NormalizationErrorResource;
use Filament\Resources\Pages\ListRecords;

final class ListNormalizationErrors extends ListRecords
{
    use ScopesImportBatch;

    protected static string $resource = NormalizationErrorResource::class;
}
