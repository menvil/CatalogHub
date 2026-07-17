<?php

namespace Tests\Unit\Architecture\PHPStan;

use CatalogHub\PHPStan\Rules\NoUnvalidatedControllerInputRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoUnvalidatedControllerInputRule> */
final class NoUnvalidatedControllerInputRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUnvalidatedControllerInputRule;
    }

    public function test_reports_only_unvalidated_request_input_access(): void
    {
        $prefix = 'Controllers must read input through a dedicated Form Request typed accessor; ';

        $this->analyse([__DIR__.'/../../../Fixtures/Architecture/Controllers/UnvalidatedInputController.php'], [
            [$prefix.'Request::input() is forbidden.', 14],
            [$prefix.'Request::string() is forbidden.', 15],
            [$prefix.'Request array access is forbidden.', 16],
            [$prefix.'Request magic properties are forbidden.', 17],
            [$prefix.'Request::query() is forbidden.', 18],
            [$prefix.'request() input access is forbidden.', 19],
        ]);
    }
}
