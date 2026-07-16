<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<MethodCall> */
final class NoDirectControllerPermissionCheckRule implements Rule
{
    /**
     * @param  list<array{class: class-string, methods: list<string>, reason: string, target: string}>  $legacyExceptions
     */
    public function __construct(private array $legacyExceptions) {}

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! ArchitectureScope::isController($scope)
            || ! $node->name instanceof Identifier
            || $node->name->toString() !== 'hasCatalogHubPermission'
            || $this->isLegacyException($scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Controllers must authorize through a policy or Gate instead of calling hasCatalogHubPermission() directly.')
                ->identifier('cataloghub.controller.directPermissionCheck')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function isLegacyException(Scope $scope): bool
    {
        $className = $scope->getClassReflection()?->getName();
        $methodName = $scope->getFunctionName();

        foreach ($this->legacyExceptions as $exception) {
            if ($exception['class'] === $className && in_array($methodName, $exception['methods'], true)) {
                return true;
            }
        }

        return false;
    }
}
