<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

use CatalogHub\PHPStan\Support\EloquentCallInspector;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<CallLike> */
final class NoControllerEloquentQueryRule implements Rule
{
    /** @var list<string> */
    private const MODEL_READ_METHODS = [
        'fresh',
        'load',
        'loadAggregate',
        'loadAvg',
        'loadCount',
        'loadExists',
        'loadMax',
        'loadMin',
        'loadMissing',
        'loadMorph',
        'loadMorphCount',
        'loadSum',
        'newModelQuery',
        'newQuery',
        'newQueryWithoutRelationships',
        'refresh',
    ];

    /** @var array<string, true> */
    private array $reported = [];

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! ArchitectureScope::isController($scope)
            || (! $node instanceof MethodCall
                && ! $node instanceof NullsafeMethodCall
                && ! $node instanceof StaticCall)
            || ! $node->name instanceof Node\Identifier
            || ! $this->isControllerOwnedQuery($node, $scope)) {
            return [];
        }

        $method = $node->name->toString();
        $message = sprintf(
            'HTTP controllers must delegate Eloquent queries to a Query Object; %s() is not allowed here.',
            $method,
        );
        $key = implode(':', [$scope->getFile(), (string) $node->getStartLine(), $message]);

        if (isset($this->reported[$key])) {
            return [];
        }

        $this->reported[$key] = true;

        return [
            RuleErrorBuilder::message($message)
                ->identifier('cataloghub.controller.eloquentQuery')
                ->build(),
        ];
    }

    private function isControllerOwnedQuery(
        MethodCall|NullsafeMethodCall|StaticCall $node,
        Scope $scope,
    ): bool {
        if ($node instanceof StaticCall) {
            return EloquentCallInspector::hasModelOrQueryReceiver($scope, $node);
        }

        if (EloquentCallInspector::hasQueryReceiver($scope, $node)) {
            return true;
        }

        $method = $node->name instanceof Node\Identifier ? $node->name->toString() : '';

        return EloquentCallInspector::returnsQuery($scope, $node)
            || (in_array($method, self::MODEL_READ_METHODS, true)
                && EloquentCallInspector::hasModelOrQueryReceiver($scope, $node));
    }
}
