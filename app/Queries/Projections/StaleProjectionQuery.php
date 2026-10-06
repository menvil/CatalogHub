<?php

namespace App\Queries\Projections;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

final class StaleProjectionQuery implements RawSqlPersistenceBoundary
{
    /** @return Builder<SiteProductProjection> */
    public function productsForSite(Site $site): Builder
    {
        return SiteProductProjection::query()
            ->join(
                'central_products',
                'central_products.id',
                '=',
                'site_product_projections.central_product_id',
            )
            ->where('site_product_projections.site_id', $site->getKey())
            ->leftJoin('central_categories', 'central_categories.id', '=', 'central_products.central_category_id')
            ->where(fn ($query) => $query->whereColumn('site_product_projections.central_product_version', '!=', 'central_products.version')
                ->orWhereNull('site_product_projections.central_product_version')
                ->orWhere('site_product_projections.attribute_identity_version', '!=', 2)
                ->orWhereColumn('site_product_projections.schema_revision', '!=', 'central_categories.schema_revision'))
            ->select([
                'site_product_projections.id as projection_id',
                'site_product_projections.central_product_id',
            ]);
    }

    /** @return Builder<SiteCategoryProjection> */
    public function categoriesForSite(Site $site): Builder
    {
        return SiteCategoryProjection::query()
            ->join(
                'central_categories',
                'central_categories.id',
                '=',
                'site_category_projections.central_category_id',
            )
            ->where('site_category_projections.site_id', $site->getKey())
            ->where(fn ($query) => $query->whereRaw($this->versionMismatchSql('site_category_projections.central_category_version', 'central_categories.updated_at'))
                ->orWhere('site_category_projections.attribute_identity_version', '!=', 2)
                ->orWhereColumn('site_category_projections.schema_revision', '!=', 'central_categories.schema_revision'))
            ->select([
                'site_category_projections.id as projection_id',
                'site_category_projections.central_category_id',
            ]);
    }

    private function versionMismatchSql(
        string $projectionVersionColumn,
        string $sourceUpdatedAtColumn,
    ): string {
        $sourceVersionExpression = $this->sourceVersionExpression($sourceUpdatedAtColumn);

        return "(({$projectionVersionColumn} IS NULL AND {$sourceUpdatedAtColumn} IS NOT NULL)"
            ." OR ({$projectionVersionColumn} IS NOT NULL AND {$sourceUpdatedAtColumn} IS NULL)"
            ." OR ({$projectionVersionColumn} IS NOT NULL AND {$sourceUpdatedAtColumn} IS NOT NULL"
            ." AND {$projectionVersionColumn} <> {$sourceVersionExpression}))";
    }

    private function sourceVersionExpression(string $updatedAtColumn): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "CAST(EXTRACT(EPOCH FROM {$updatedAtColumn}) AS BIGINT)",
            'mysql', 'mariadb' => "UNIX_TIMESTAMP({$updatedAtColumn})",
            'sqlite' => "CAST(strftime('%s', {$updatedAtColumn}) AS INTEGER)",
            'sqlsrv' => "DATEDIFF_BIG(second, '1970-01-01', {$updatedAtColumn})",
            default => throw new LogicException('Unsupported database driver for projection stale detection.'),
        };
    }
}
