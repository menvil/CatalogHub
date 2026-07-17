<?php

namespace App\Services\ArchitectureFixtures;

use App\Models\SiteSearchDocument;
use Illuminate\Database\Eloquent\Builder;

final class NullableRawQuery
{
    /** @param Builder<SiteSearchDocument>|null $query */
    public function query(?Builder $query): void
    {
        $query?->whereRaw('id = 1');
    }
}
