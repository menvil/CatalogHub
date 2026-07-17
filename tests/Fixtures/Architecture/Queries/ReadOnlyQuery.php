<?php

namespace App\Queries\ArchitectureFixtures;

use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;

final class ReadOnlyQuery
{
    /** @return Collection<int, Site> */
    public function get(): Collection
    {
        return Site::query()->with('market')->orderBy('id')->get();
    }
}
