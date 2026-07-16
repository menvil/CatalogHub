<?php

namespace Tests\Unit\Architecture\PHPStan;

use CatalogHub\PHPStan\Rules\NoInlineControllerValidationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoInlineControllerValidationRule> */
class NoInlineControllerValidationRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoInlineControllerValidationRule([]);
    }

    public function test_reports_inline_http_validation_in_controllers(): void
    {
        $message = 'HTTP validation must be moved from the controller to a dedicated Form Request.';

        $this->analyse([__DIR__.'/../../../Fixtures/Architecture/Controllers/InvalidValidationController.php'], [
            [$message, 13],
            [$message, 19],
            [$message, 25],
        ]);
    }
}
