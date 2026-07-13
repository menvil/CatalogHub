<?php

namespace App\Filament\Resources\RawProductResource\Pages;

use App\Filament\Concerns\ScopesImportBatch;
use App\Filament\Resources\RawProductResource;
use Filament\Resources\Pages\ListRecords;

final class ListRawProducts extends ListRecords
{
    use ScopesImportBatch;

    protected static string $resource = RawProductResource::class;
}
