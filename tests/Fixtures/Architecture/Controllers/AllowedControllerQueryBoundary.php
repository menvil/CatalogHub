<?php

namespace App\Http\Controllers\ArchitectureFixtures;

use App\Models\Site;
use Illuminate\Support\Collection;

final class AllowedControllerQueryBoundary
{
    /** @param Collection<int, Site> $sites */
    public function show(Site $site, ReadOnlySiteLookup $query, Collection $sites): int
    {
        return $query->identifier($site) + (int) $sites->get(0)?->getKey();
    }
}

final class ReadOnlySiteLookup
{
    public function identifier(Site $site): int
    {
        return (int) $site->getKey();
    }
}
