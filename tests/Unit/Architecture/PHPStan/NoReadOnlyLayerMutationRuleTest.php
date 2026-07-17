<?php

namespace Tests\Unit\Architecture\PHPStan;

use CatalogHub\PHPStan\Rules\NoReadOnlyLayerMutationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoReadOnlyLayerMutationRule> */
final class NoReadOnlyLayerMutationRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoReadOnlyLayerMutationRule;
    }

    public function test_queries_and_policies_cannot_mutate_lock_or_open_transactions(): void
    {
        $this->analyse([
            __DIR__.'/../../../Fixtures/Architecture/Queries/MutatingQuery.php',
            __DIR__.'/../../../Fixtures/Architecture/Policies/MutatingPolicy.php',
        ], [
            ['Query Objects are read-only; Eloquent save() is not allowed.', 13],
            ['Query Objects are read-only; Eloquent update() is not allowed.', 18],
            ['Query Objects are read-only; Eloquent create() is not allowed.', 23],
            ['Query Objects are read-only; lockForUpdate() belongs in an Action transaction.', 28],
            ['Query Objects are read-only; database transactions are not allowed.', 33],
            ['Query Objects are read-only; database transactions are not allowed.', 38],
            ['Policies are read-only; Eloquent update() is not allowed.', 11],
        ]);

        $this->analyse([
            __DIR__.'/../../../Fixtures/Architecture/Queries/ReadOnlyQuery.php',
        ], []);
    }
}
