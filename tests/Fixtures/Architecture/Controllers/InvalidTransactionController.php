<?php

namespace App\Http\Controllers\ArchitectureFixtures;

use App\Http\Controllers\Controller;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

final class InvalidTransactionController extends Controller
{
    public function __invoke(): void
    {
        DB::transaction(static fn (): null => null);
    }

    public function manualConnectionTransaction(ConnectionInterface $connection): void
    {
        $connection->beginTransaction();
    }

    public function nullableConnectionTransaction(?ConnectionInterface $connection): void
    {
        $connection?->rollBack();
    }

    public function nonDatabaseLookalike(FixtureTransactionManager $manager): void
    {
        $manager->transaction(static fn (): null => null);
    }
}

final class FixtureTransactionManager
{
    public function transaction(callable $callback): mixed
    {
        return $callback();
    }
}

namespace App\Filament\ArchitectureFixtures;

use Illuminate\Support\Facades\DB;

final class InvalidFilamentTransaction
{
    public function save(): void
    {
        DB::beginTransaction();
    }
}

namespace App\Http\Requests\ArchitectureFixtures;

use Illuminate\Support\Facades\DB;

final class InvalidFormRequestTransaction
{
    public function persist(): void
    {
        DB::rollBack();
    }
}

namespace App\Livewire\ArchitectureFixtures;

use Illuminate\Support\Facades\DB;

final class InvalidLivewireTransaction
{
    public function persist(): void
    {
        DB::commit();
    }
}
