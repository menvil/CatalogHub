<?php

namespace App\Services\ArchitectureFixtures;

use Illuminate\Database\Eloquent\Builder;

final class ApprovedRawQuery
{
    /** @param Builder<*> $query */
    public function query(Builder $query, int $id): void
    {
        $query->whereRaw('id = ?', [$id]);
    }

    /** @param Builder<*> $query */
    public function unrelated(Builder $query): void
    {
        $query->whereRaw('id = 1');
    }
}
