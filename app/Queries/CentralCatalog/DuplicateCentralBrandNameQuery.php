<?php

namespace App\Queries\CentralCatalog;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Models\CentralCatalog\CentralBrand;

final class DuplicateCentralBrandNameQuery implements RawSqlPersistenceBoundary
{
    public function exists(string $normalizedName, ?CentralBrand $except = null): bool
    {
        $query = CentralBrand::query()->whereRaw('LOWER(name) = LOWER(?)', [$normalizedName]);

        if ($except !== null) {
            $query->where($except->getKeyName(), '!=', $except->getKey());
        }

        return $query->exists();
    }
}
