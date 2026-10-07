<?php

namespace App\Services\AttributeGlobalization;

use App\Models\CentralCatalog\AttributeIdentityScope;
use Illuminate\Support\Facades\DB;
use LogicException;

/** Transaction mutex before Category/definition locks for normal membership mutations and rebuilds. */
final class AttributeIdentityLock
{
    public function acquire(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Attribute membership locking requires a transaction.');
        }
        // A write obtains SQLite's writer lock; PostgreSQL/MariaDB hold the same row until transaction end.
        AttributeIdentityScope::query()->whereKey(1)->toBase()->update(['id' => 1]);
        AttributeIdentityScope::query()->whereKey(1)->lockForUpdate()->firstOrFail();
    }
}
