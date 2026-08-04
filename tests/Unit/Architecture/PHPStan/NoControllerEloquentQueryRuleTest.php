<?php

namespace Tests\Unit\Architecture\PHPStan;

use CatalogHub\PHPStan\Rules\NoControllerEloquentQueryRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoControllerEloquentQueryRule> */
final class NoControllerEloquentQueryRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoControllerEloquentQueryRule;
    }

    public function test_rejects_controller_owned_queries_without_blocking_query_objects_or_collections(): void
    {
        $this->analyse([
            __DIR__.'/../../../Fixtures/Architecture/Controllers/ControllerEloquentReads.php',
        ], [
            ['HTTP controllers must delegate Eloquent queries to a Query Object; query() is not allowed here.', 13],
            ['HTTP controllers must delegate Eloquent queries to a Query Object; where() is not allowed here.', 13],
            ['HTTP controllers must delegate Eloquent queries to a Query Object; get() is not allowed here.', 13],
            ['HTTP controllers must delegate Eloquent queries to a Query Object; loadMissing() is not allowed here.', 18],
            ['HTTP controllers must delegate Eloquent queries to a Query Object; features() is not allowed here.', 24],
            ['HTTP controllers must delegate Eloquent queries to a Query Object; latest() is not allowed here.', 24],
            ['HTTP controllers must delegate Eloquent queries to a Query Object; get() is not allowed here.', 24],
            ['HTTP controllers must delegate Eloquent queries to a Query Object; load() is not allowed here.', 29],
        ]);

        $this->analyse([
            __DIR__.'/../../../Fixtures/Architecture/Controllers/AllowedControllerQueryBoundary.php',
        ], []);
    }
}
