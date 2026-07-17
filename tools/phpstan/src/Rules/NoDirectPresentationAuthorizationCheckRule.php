<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

use App\Models\User;
use App\Support\PermissionMatrix;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

/** @implements Rule<Node> */
final class NoDirectPresentationAuthorizationCheckRule implements Rule
{
    /** @var list<string> */
    private const RESTRICTED_USER_METHODS = [
        'canManageImports',
        'hasCatalogHubPermission',
        'hasRole',
        'isCatalogEditor',
        'isCentralAdmin',
        'isModerator',
        'isSiteAdmin',
        'isSuperAdmin',
        'isTranslator',
    ];

    /** @var array<string, true> */
    private array $reported = [];

    public function getNodeType(): string
    {
        return Node::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! ArchitectureScope::isPresentationLayer($scope)) {
            return [];
        }

        if ($node instanceof ArrayDimFetch
            && $this->isUser($scope, $node->var)
            && $node->dim instanceof String_
            && $node->dim->value === 'role') {
            return $this->errorsOnce($node, $scope, 'Presentation classes must authorize through a policy or Gate; direct User role access is forbidden.');
        }

        if ($node instanceof PropertyFetch
            && $node->name instanceof Identifier
            && $node->name->toString() === 'role'
            && $this->isUser($scope, $node->var)) {
            return $this->errorsOnce($node, $scope, 'Presentation classes must authorize through a policy or Gate; direct User role access is forbidden.');
        }

        if (! ($node instanceof MethodCall || $node instanceof NullsafeMethodCall)
            || ! $node->name instanceof Identifier) {
            return [];
        }

        if ($this->isPermissionMatrix($scope, $node->var)
            && $node->name->toString() === 'allows') {
            return $this->errorsOnce($node, $scope, 'Presentation classes must authorize through a policy or Gate; direct PermissionMatrix access is forbidden.');
        }

        if (! $this->isUser($scope, $node->var)) {
            return [];
        }

        $method = $node->name->toString();
        $isRoleAttribute = $method === 'getAttribute'
            && isset($node->args[0])
            && $node->args[0]->value instanceof String_
            && $node->args[0]->value->value === 'role';

        if (! $isRoleAttribute && ! in_array($method, self::RESTRICTED_USER_METHODS, true)) {
            return [];
        }

        return $this->errorsOnce($node, $scope, sprintf(
            'Presentation classes must authorize through a policy or Gate; direct User::%s() checks are forbidden.',
            $method,
        ));
    }

    private function isUser(Scope $scope, Expr $expression): bool
    {
        return (new ObjectType(User::class))->isSuperTypeOf(
            TypeCombinator::removeNull($scope->getType($expression)),
        )->yes();
    }

    private function isPermissionMatrix(Scope $scope, Expr $expression): bool
    {
        return (new ObjectType(PermissionMatrix::class))->isSuperTypeOf(
            TypeCombinator::removeNull($scope->getType($expression)),
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
                ->identifier('cataloghub.presentation.directAuthorizationCheck')
                ->build(),
        ];
    }
}
