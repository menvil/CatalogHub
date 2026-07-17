<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

use CatalogHub\PHPStan\Support\EloquentCallInspector;
use CatalogHub\PHPStan\Support\EloquentMutationDetector;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

/** @implements Rule<CallLike> */
final class NoReadOnlyLayerMutationRule implements Rule
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
        $layer = ArchitectureScope::readOnlyLayer($scope);

        if ($layer === null
            || (! $node instanceof MethodCall
                && ! $node instanceof NullsafeMethodCall
                && ! $node instanceof StaticCall)) {
            return [];
        }

        if ($this->isTransaction($node, $scope)) {
            return $this->errorsOnce($node, $scope, "{$layer} are read-only; database transactions are not allowed.");
        }

        if (($node instanceof MethodCall || $node instanceof NullsafeMethodCall)
            && $node->name instanceof Identifier
            && in_array($node->name->toString(), ['lockForUpdate', 'sharedLock'], true)
            && EloquentCallInspector::hasQueryReceiver($scope, $node)) {
            return $this->errorsOnce($node, $scope, sprintf(
                '%s are read-only; %s() belongs in an Action transaction.',
                $layer,
                $node->name->toString(),
            ));
        }

        $method = EloquentMutationDetector::mutationMethod($scope, $node);

        if ($method === null) {
            return [];
        }

        return $this->errorsOnce($node, $scope, sprintf(
            '%s are read-only; Eloquent %s() is not allowed.',
            $layer,
            $method,
        ));
    }

    private function isTransaction(
        MethodCall|NullsafeMethodCall|StaticCall $node,
        Scope $scope,
    ): bool {
        if ($node instanceof StaticCall) {
            return $node->class instanceof Name
                && $node->name instanceof Identifier
                && $scope->resolveName($node->class) === DB::class
                && in_array($node->name->toString(), ['beginTransaction', 'commit', 'rollBack', 'transaction'], true);
        }

        return $node->name instanceof Identifier
            && in_array($node->name->toString(), ['beginTransaction', 'commit', 'rollBack', 'transaction'], true)
            && (new ObjectType(ConnectionInterface::class))->isSuperTypeOf(
                TypeCombinator::removeNull($scope->getType($node->var)),
            )->yes();
    }

    /** @return list<IdentifierRuleError> */
    private function errorsOnce(Node $node, Scope $scope, string $message): array
    {
        $key = implode(':', [$scope->getFile(), (string) $node->getStartLine(), $message]);

        if (isset($this->reported[$key])) {
            return [];
        }

        $this->reported[$key] = true;

        return [
            RuleErrorBuilder::message($message)
                ->identifier('cataloghub.readOnlyLayer.mutation')
                ->build(),
        ];
    }
}
