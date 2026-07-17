<?php

namespace Tests\Unit\Architecture\PHPStan;

use CatalogHub\PHPStan\Rules\RestrictedDatabaseFacadeRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<RestrictedDatabaseFacadeRule> */
class RestrictedDatabaseFacadeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RestrictedDatabaseFacadeRule;
    }

    public function test_reports_low_level_queries_and_presentation_transactions(): void
    {
        $this->analyse([
            __DIR__.'/../../../Fixtures/Architecture/Services/InvalidDatabaseService.php',
            __DIR__.'/../../../Fixtures/Architecture/Controllers/InvalidTransactionController.php',
        ], [
            ['Low-level DB facade queries must be replaced with Eloquent or isolated in an approved Query Object.', 11],
            ['Presentation classes must not manage database transactions; move orchestration to an Action or Service.', 13],
            ['Presentation classes must not manage database transactions; move orchestration to an Action or Service.', 18],
            ['Presentation classes must not manage database transactions; move orchestration to an Action or Service.', 23],
            ['Presentation classes must not manage database transactions; move orchestration to an Action or Service.', 48],
            ['Presentation classes must not manage database transactions; move orchestration to an Action or Service.', 60],
            ['Presentation classes must not manage database transactions; move orchestration to an Action or Service.', 72],
        ]);
    }
}
