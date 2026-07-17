<?php

namespace Tests\Unit\Architecture\PHPStan;

use CatalogHub\PHPStan\Rules\NoPresentationEloquentMutationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoPresentationEloquentMutationRule> */
final class NoPresentationEloquentMutationRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoPresentationEloquentMutationRule;
    }

    public function test_rejects_eloquent_mutations_without_blocking_framework_lookalikes(): void
    {
        $this->analyse([__DIR__.'/../../../Fixtures/Architecture/Controllers/PresentationEloquentMutation.php'], [
            ['Presentation classes must delegate Eloquent update() mutations to an Action.', 13],
            ['Presentation classes must delegate Eloquent create() mutations to an Action.', 18],
            ['Presentation classes must delegate Eloquent updateOrCreate() mutations to an Action.', 23],
            ['Presentation classes must delegate Eloquent delete() mutations to an Action.', 28],
            ['Presentation classes must delegate Eloquent updateOrCreate() mutations to an Action.', 33],
        ]);
    }
}
