<?php

namespace App\Http\Controllers\ArchitectureFixtures;

use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;

final class ControllerEloquentReads
{
    /** @return Collection<int, Site> */
    public function index(): Collection
    {
        return Site::query()->where('status', 'active')->get();
    }

    public function load(Site $site): Site
    {
        return $site->loadMissing('market');
    }

    /** @return Collection<int, mixed> */
    public function relation(Site $site): Collection
    {
        return $site->features()->latest()->get();
    }

    public function nullable(?Site $site): ?Site
    {
        return $site?->load('market');
    }
}
