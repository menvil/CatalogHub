<?php

namespace Tests\Unit\Architecture\PHPStan;

use CatalogHub\PHPStan\Rules\NoDirectPresentationAuthorizationCheckRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoDirectPresentationAuthorizationCheckRule> */
final class NoDirectPresentationAuthorizationCheckRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoDirectPresentationAuthorizationCheckRule;
    }

    public function test_rejects_direct_authorization_checks_throughout_presentation(): void
    {
        $this->analyse([__DIR__.'/../../../Fixtures/Architecture/Controllers/DirectPresentationAuthorizationCheck.php'], [
            ['Presentation classes must authorize through a policy or Gate; direct User::hasCatalogHubPermission() checks are forbidden.', 11],
            ['Presentation classes must authorize through a policy or Gate; direct User::isSuperAdmin() checks are forbidden.', 23],
            ['Presentation classes must authorize through a policy or Gate; direct User role access is forbidden.', 35],
            ['Presentation classes must authorize through a policy or Gate; direct User::canManageImports() checks are forbidden.', 49],
            ['Presentation classes must authorize through a policy or Gate; direct User::getAttribute() checks are forbidden.', 50],
            ['Presentation classes must authorize through a policy or Gate; direct User role access is forbidden.', 51],
            ['Presentation classes must authorize through a policy or Gate; direct PermissionMatrix access is forbidden.', 52],
            ['Presentation classes must authorize through a policy or Gate; direct User role access is forbidden.', 52],
        ]);
    }
}
