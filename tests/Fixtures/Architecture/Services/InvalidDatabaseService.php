<?php

namespace App\Services\ArchitectureFixtures;

use Illuminate\Support\Facades\DB;

final class InvalidDatabaseService
{
    public function query(): void
    {
        DB::table('users')->count();
    }

    public function transaction(): void
    {
        DB::transaction(static fn (): null => null);
    }
}
