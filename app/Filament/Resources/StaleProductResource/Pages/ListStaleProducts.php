<?php

namespace App\Filament\Resources\StaleProductResource\Pages;

use App\Filament\Resources\StaleProductResource;
use Filament\Resources\Pages\ListRecords;

final class ListStaleProducts extends ListRecords
{
    protected static string $resource = StaleProductResource::class;
}
