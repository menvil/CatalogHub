<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<MethodCall> */
final class RestrictedRawQueryRule implements Rule
{
    /** @var list<string> */
    private const RAW_METHODS = [
        'addSelectRaw',
        'fromRaw',
        'groupByRaw',
        'havingRaw',
        'orHavingRaw',
        'orWhereRaw',
        'orderByRaw',
        'selectRaw',
        'whereRaw',
    ];

    /**
     * @param  list<array{class: class-string, methods: list<string>, reason: string, bindings: 'required'|'literal_only'|'internal_only', behaviorTests: list<string>, status: 'approved'}>  $rawSqlExceptions
     */
    public function __construct(private array $rawSqlExceptions) {}

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! ArchitectureScope::isApplicationCode($scope)
            || ! $node->name instanceof Identifier
            || ! in_array($node->name->toString(), self::RAW_METHODS, true)) {
            return [];
        }

        $method = $node->name->toString();
        $className = $scope->getClassReflection()?->getName();

        foreach ($this->rawSqlExceptions as $exception) {
            if ($exception['class'] !== $className || ! in_array($method, $exception['methods'], true)) {
                continue;
            }

            if ($exception['bindings'] === 'required' && count($node->getArgs()) < 2) {
                return [
                    RuleErrorBuilder::message('This approved raw SQL call requires a separate bindings argument.')
                        ->identifier('cataloghub.database.rawSqlBindings')
                        ->line($node->getStartLine())
                        ->build(),
                ];
            }

            if ($exception['bindings'] === 'literal_only'
                && (! isset($node->getArgs()[0]) || ! $node->getArgs()[0]->value instanceof String_)) {
                return [
                    RuleErrorBuilder::message('This raw SQL exception only permits a literal SQL string.')
                        ->identifier('cataloghub.database.rawSqlLiteral')
                        ->line($node->getStartLine())
                        ->build(),
                ];
            }

            return [];
        }

        return [
            RuleErrorBuilder::message('Raw query expressions must be replaced with Eloquent or isolated in an approved Query Object.')
                ->identifier('cataloghub.database.restrictedRawQuery')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
