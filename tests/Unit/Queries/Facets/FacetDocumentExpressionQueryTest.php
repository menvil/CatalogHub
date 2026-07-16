<?php

namespace Tests\Unit\Queries\Facets;

use App\Models\SiteSearchDocument;
use App\Queries\Facets\FacetDocumentExpressionQuery;
use InvalidArgumentException;
use Tests\TestCase;

final class FacetDocumentExpressionQueryTest extends TestCase
{
    public function test_numeric_constraint_rejects_unsafe_json_identifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(FacetDocumentExpressionQuery::class)->whereNumeric(
            SiteSearchDocument::query(),
            'filter_values_json',
            "rating') OR 1=1 --",
            '>=',
            '1',
        );
    }

    public function test_numeric_constraint_rejects_unsupported_operators(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(FacetDocumentExpressionQuery::class)->whereNumeric(
            SiteSearchDocument::query(),
            'filter_values_json',
            'rating',
            '<>',
            '1',
        );
    }
}
