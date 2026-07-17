<?php

namespace App\Queries\ArchitectureFixtures;

use App\Models\Site;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

final class MutatingQuery
{
    public function save(Site $site): void
    {
        $site->save();
    }

    public function update(): void
    {
        Site::query()->update(['name' => 'Changed']);
    }

    public function create(Site $site): void
    {
        $site->features()->create(['feature_key' => 'reviews']);
    }

    public function lock(): void
    {
        Site::query()->lockForUpdate()->first();
    }

    public function transaction(): void
    {
        DB::transaction(static fn (): null => null);
    }

    public function manualTransaction(ConnectionInterface $connection): void
    {
        $connection->beginTransaction();
    }
}
