<?php

namespace App\Queries\Facets;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Models\SiteSearchDocument;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class FacetDocumentExpressionQuery implements RawSqlPersistenceBoundary
{
    /** @param Builder<SiteSearchDocument> $query */
    public function orderByRatingDesc(Builder $query): Builder
    {
        return $query
            ->orderByRaw($this->numericJsonValueExpression($query, 'sort_values_json', 'rating').' DESC')
            ->orderByDesc('id');
    }

    /** @param Builder<SiteSearchDocument> $query */
    public function orderByPriceAsc(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN min_price IS NULL THEN 1 ELSE 0 END')
            ->orderBy('min_price')
            ->orderBy('id');
    }

    /** @param Builder<SiteSearchDocument> $query */
    public function orderByPriceDesc(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN min_price IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('min_price')
            ->orderByDesc('id');
    }

    /** @param Builder<SiteSearchDocument> $query */
    public function whereNumeric(
        Builder $query,
        string $column,
        string $code,
        string $operator,
        string $serializedValue,
    ): Builder {
        if (! in_array($operator, ['>=', '<='], true)) {
            throw new InvalidArgumentException('Unsupported numeric facet operator.');
        }

        $valueExpression = $this->numericJsonValueExpression($query, $column, $code);
        $placeholder = match ($query->getModel()->getConnection()->getDriverName()) {
            'mysql', 'mariadb' => 'CAST(? AS DECIMAL(65, 20))',
            'pgsql' => 'CAST(? AS NUMERIC)',
            'sqlsrv' => 'TRY_CAST(? AS FLOAT)',
            default => 'CAST(? AS REAL)',
        };

        return $query->whereRaw("{$valueExpression} {$operator} {$placeholder}", [$serializedValue]);
    }

    /** @param Builder<SiteSearchDocument> $query */
    private function numericJsonValueExpression(Builder $query, string $column, string $code): string
    {
        if (! preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/', $column)
            || ! preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/', $code)) {
            throw new InvalidArgumentException('Unsafe numeric facet identifier.');
        }

        return match ($query->getModel()->getConnection()->getDriverName()) {
            'mysql', 'mariadb' => "CAST(JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, '$.\"{$code}\"')) AS DECIMAL(65, 20))",
            'pgsql' => "CAST(\"{$column}\"->>'{$code}' AS NUMERIC)",
            'sqlsrv' => "TRY_CAST(JSON_VALUE([{$column}], '$.{$code}') AS FLOAT)",
            default => "CAST(json_extract(\"{$column}\", '$.\"{$code}\"') AS REAL)",
        };
    }
}
