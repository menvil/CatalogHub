<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

/** @implements Rule<CallLike> */
final class RestrictedRawQueryRule implements Rule
{
    /** @var array<string, true> */
    private array $reportedErrors = [];

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
     * @param  list<array{class: class-string, ownerMethods: list<string>, methods: list<string>, reason: string, bindings: 'required'|'literal_only'|'internal_only', behaviorTests: list<string>, status: 'approved'}>  $rawSqlExceptions
     */
    public function __construct(private array $rawSqlExceptions) {}

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! ArchitectureScope::isApplicationCode($scope)
            || (! $node instanceof MethodCall
                && ! $node instanceof NullsafeMethodCall
                && ! $node instanceof StaticCall)
            || ! $node->name instanceof Identifier
            || ! in_array($node->name->toString(), self::RAW_METHODS, true)
            || ! $this->isDatabaseQuery($node, $scope)) {
            return [];
        }

        $method = $node->name->toString();
        $className = $scope->getClassReflection()?->getName();
        $ownerMethod = $scope->getFunctionName();

        foreach ($this->rawSqlExceptions as $exception) {
            if ($exception['class'] !== $className
                || ! in_array($ownerMethod, $exception['ownerMethods'], true)
                || ! in_array($method, $exception['methods'], true)) {
                continue;
            }

            if ($exception['bindings'] === 'required' && count($node->getArgs()) < 2) {
                return $this->errorOnce(
                    $node,
                    $scope,
                    'cataloghub.database.rawSqlBindings',
                    'This approved raw SQL call requires a separate bindings argument.',
                );
            }

            if ($exception['bindings'] === 'literal_only'
                && (! isset($node->getArgs()[0]) || ! $node->getArgs()[0]->value instanceof String_)) {
                return $this->errorOnce(
                    $node,
                    $scope,
                    'cataloghub.database.rawSqlLiteral',
                    'This raw SQL exception only permits a literal SQL string.',
                );
            }

            return [];
        }

        return $this->errorOnce(
            $node,
            $scope,
            'cataloghub.database.restrictedRawQuery',
            'Raw query expressions must be replaced with Eloquent or isolated in an approved Query Object method.',
        );
    }

    private function isDatabaseQuery(MethodCall|NullsafeMethodCall|StaticCall $node, Scope $scope): bool
    {
        if ($node instanceof MethodCall || $node instanceof NullsafeMethodCall) {
            $type = TypeCombinator::removeNull($scope->getType($node->var));

            foreach ([EloquentBuilder::class, QueryBuilder::class, Relation::class] as $builderClass) {
                if ((new ObjectType($builderClass))->isSuperTypeOf($type)->yes()) {
                    return true;
                }
            }

            return false;
        }

        if (! $node->class instanceof Name) {
            return false;
        }

        $type = $scope->resolveTypeByName($node->class);

        return (new ObjectType(Model::class))->isSuperTypeOf($type)->yes()
            || (new ObjectType(EloquentBuilder::class))->isSuperTypeOf($type)->yes()
            || (new ObjectType(QueryBuilder::class))->isSuperTypeOf($type)->yes();
    }

    /** @return list<RuleError> */
    private function errorOnce(MethodCall|NullsafeMethodCall|StaticCall $node, Scope $scope, string $identifier, string $message): array
    {
        $key = $scope->getFile().':'.$node->getStartLine().':'.$identifier.':'.($node->name instanceof Identifier ? $node->name->toString() : 'unknown');

        if (isset($this->reportedErrors[$key])) {
            return [];
        }

        $this->reportedErrors[$key] = true;

        return [
            RuleErrorBuilder::message($message)
                ->identifier($identifier)
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
