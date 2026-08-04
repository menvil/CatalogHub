<?php

namespace App\Queries\Sync;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Models\SiteProduct;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class StaleProductVersionGapQuery implements RawSqlPersistenceBoundary
{
    /**
     * @param  Builder<SiteProduct>  $query
     * @return Builder<SiteProduct>
     */
    public function apply(Builder $query, int $minimumGap): Builder
    {
        if ($minimumGap < 1) {
            throw new InvalidArgumentException('The minimum version gap must be positive.');
        }

        return $query->whereRaw(
            'central_products.version - site_products.published_version >= ?',
            [$minimumGap],
        );
    }
}
