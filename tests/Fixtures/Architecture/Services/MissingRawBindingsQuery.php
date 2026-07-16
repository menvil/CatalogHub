<?php

namespace App\Services\ArchitectureFixtures;

use Illuminate\Database\Eloquent\Builder;

final class MissingRawBindingsQuery
{
    /** @param Builder<*> $query */
    public function query(Builder $query): void
    {
        $query->whereRaw('id = 1');
    }
}
