<?php

namespace Tests\Unit\Architecture\PHPStan;

use CatalogHub\PHPStan\Rules\RegisteredPaginationBoundaryRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<RegisteredPaginationBoundaryRule> */
final class RegisteredPaginationBoundaryRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RegisteredPaginationBoundaryRule([
            [
                'class' => 'App\Queries\ArchitectureFixtures\RegisteredPagination',
                'method' => 'paginate',
                'uniqueOrder' => ['id'],
                'behaviorTests' => [__FILE__],
                'status' => 'approved',
            ],
        ]);
    }

    public function test_requires_registered_query_object_pagination(): void
    {
        $this->analyse([
            __DIR__.'/../../../Fixtures/Architecture/Queries/UnregisteredPagination.php',
        ], [
            ['Paginated Query Object methods require an approved stable-pagination registry entry and behavior test.', 13],
        ]);

        $this->analyse([
            __DIR__.'/../../../Fixtures/Architecture/Queries/RegisteredPagination.php',
        ], []);

        $this->analyse([
            __DIR__.'/../../../Fixtures/Architecture/Services/OutsideBoundaryPagination.php',
        ], [
            ['Eloquent pagination must be owned by a registered Query Object.', 13],
        ]);
    }
}
