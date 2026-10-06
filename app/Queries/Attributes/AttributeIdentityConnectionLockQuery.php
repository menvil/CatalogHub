<?php

namespace App\Queries\Attributes;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Models\CentralCatalog\AttributeIdentityScope;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** MariaDB's connection lock survives the implicit commits made by DDL. */
final class AttributeIdentityConnectionLockQuery implements RawSqlPersistenceBoundary
{
    private const NAME = 'cataloghub_attribute_identity';

    public function acquire(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        $held = (new AttributeIdentityScope)->getConnection()->selectOne('SELECT IS_USED_LOCK(?) = CONNECTION_ID() AS held', [self::NAME]);
        if ((int) ($held->held ?? 0) === 1) {
            return;
        }
        $result = (new AttributeIdentityScope)->getConnection()->selectOne('SELECT GET_LOCK(?, 30) AS acquired', [self::NAME]);
        if ((int) $result->acquired !== 1) {
            throw new RuntimeException('Attribute identity lock timeout; no mutation applied.');
        }
    }

    public function release(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            (new AttributeIdentityScope)->getConnection()->selectOne('SELECT RELEASE_LOCK(?)', [self::NAME]);
        }
    }
}
