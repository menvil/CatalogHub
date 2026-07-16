<?php

namespace Tests\Unit\Architecture\PHPStan;

use CatalogHub\PHPStan\Rules\RestrictedRawQueryRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<RestrictedRawQueryRule> */
class RestrictedRawQueryRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RestrictedRawQueryRule([
            [
                'class' => 'App\\Services\\ArchitectureFixtures\\ApprovedRawQuery',
                'methods' => ['whereRaw'],
                'reason' => 'Fixture with a bound value.',
                'bindings' => 'required',
                'behaviorTests' => [__FILE__],
                'status' => 'approved',
            ],
            [
                'class' => 'App\\Services\\ArchitectureFixtures\\MissingRawBindingsQuery',
                'methods' => ['whereRaw'],
                'reason' => 'Fixture missing its required bindings argument.',
                'bindings' => 'required',
                'behaviorTests' => [__FILE__],
                'status' => 'approved',
            ],
        ]);
    }

    public function test_reports_unapproved_raw_sql_and_missing_bindings(): void
    {
        $this->analyse([
            __DIR__.'/../../../Fixtures/Architecture/Services/InvalidRawService.php',
            __DIR__.'/../../../Fixtures/Architecture/Services/ApprovedRawQuery.php',
            __DIR__.'/../../../Fixtures/Architecture/Services/MissingRawBindingsQuery.php',
        ], [
            ['Raw query expressions must be replaced with Eloquent or isolated in an approved Query Object.', 12],
            ['This approved raw SQL call requires a separate bindings argument.', 12],
        ]);
    }
}
