<?php

namespace App\Services\SchemaCutoverV2;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Frozen v2 mutex. Shared hold depth protects MariaDB DDL from transaction listeners. */
final class IdentityMutex implements RawSqlPersistenceBoundary
{
    private const NAME = 'cataloghub_attribute_identity';

    private static int $deploymentHolds = 0;

    public function acquireConnection(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        $held = DB::connection()->selectOne('SELECT IS_USED_LOCK(?) = CONNECTION_ID() AS held', [self::NAME]);
        if ((int) ($held->held ?? 0) === 1) {
            return;
        }
        $result = DB::connection()->selectOne('SELECT GET_LOCK(?, 30) AS acquired', [self::NAME]);
        if ((int) $result->acquired !== 1) {
            throw new RuntimeException('Attribute identity lock timeout; no mutation applied.');
        }
    }

    public function acquire(): void
    {
        $this->acquireConnection();
        DB::connection()->table('attribute_identity_scopes')->where('id', 1)->increment('write_epoch', 0);
        if (DB::connection()->table('attribute_identity_scopes')->where('id', 1)->lockForUpdate()->first() === null) {
            throw new RuntimeException('Missing attribute identity scope.');
        }
    }

    public function deployment(Closure $operation): void
    {
        $this->acquireConnection();
        self::$deploymentHolds++;
        try {
            $operation();
        } finally {
            self::$deploymentHolds--;
            $this->releaseAfterTransaction();
        }
    }

    public function deploymentHeld(): bool
    {
        return self::$deploymentHolds > 0;
    }

    public function releaseAfterTransaction(): void
    {
        if (! $this->deploymentHeld() && DB::transactionLevel() === 0 && in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::connection()->selectOne('SELECT RELEASE_LOCK(?)', [self::NAME]);
        }
    }
}
