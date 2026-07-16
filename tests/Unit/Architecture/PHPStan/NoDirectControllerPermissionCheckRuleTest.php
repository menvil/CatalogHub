<?php

namespace Tests\Unit\Architecture\PHPStan;

use CatalogHub\PHPStan\Rules\NoDirectControllerPermissionCheckRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoDirectControllerPermissionCheckRule> */
class NoDirectControllerPermissionCheckRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoDirectControllerPermissionCheckRule([]);
    }

    public function test_reports_direct_permission_checks_in_controllers(): void
    {
        $this->analyse([__DIR__.'/../../../Fixtures/Architecture/Controllers/InvalidPermissionController.php'], [
            ['Controllers must authorize through a policy or Gate instead of calling hasCatalogHubPermission() directly.', 12],
        ]);
    }
}
