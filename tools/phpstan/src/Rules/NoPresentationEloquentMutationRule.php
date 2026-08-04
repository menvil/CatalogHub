<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

use CatalogHub\PHPStan\Support\EloquentMutationDetector;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<CallLike> */
final class NoPresentationEloquentMutationRule implements Rule
{
    /** @var array<string, true> */
    private array $reported = [];

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! ArchitectureScope::isPresentationLayer($scope)) {
            return [];
        }

        $method = EloquentMutationDetector::mutationMethod($scope, $node);

        if ($method === null) {
            return [];
        }

        $message = sprintf(
            'Presentation classes must delegate Eloquent %s() mutations to an Action.',
            $method,
        );
        $key = implode(':', [$scope->getFile(), (string) $node->getStartLine(), $message]);

        if (isset($this->reported[$key])) {
            return [];
        }

        $this->reported[$key] = true;

        return [
            RuleErrorBuilder::message($message)
                ->identifier('cataloghub.presentation.eloquentMutation')
                ->build(),
        ];
    }
}
