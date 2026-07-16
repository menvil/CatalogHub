<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/** @implements Rule<Expr> */
final class NoInlineControllerValidationRule implements Rule
{
    /**
     * @param  list<array{class: class-string, methods: list<string>, reason: string, target: string}>  $legacyExceptions
     */
    public function __construct(private array $legacyExceptions) {}

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! ArchitectureScope::isController($scope)
            || ! $this->isInlineValidation($node, $scope)
            || $this->isLegacyException($scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('HTTP validation must be moved from the controller to a dedicated Form Request.')
                ->identifier('cataloghub.controller.inlineValidation')
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

    private function isInlineValidation(Expr $node, Scope $scope): bool
    {
        if ($node instanceof MethodCall
            && $node->name instanceof Identifier
            && in_array($node->name->toString(), ['validate', 'validateWithBag'], true)) {
            if ($node->var instanceof Variable && $node->var->name === 'this') {
                return true;
            }

            return (new ObjectType(Request::class))->isSuperTypeOf($scope->getType($node->var))->yes();
        }

        if ($node instanceof StaticCall && $node->class instanceof Name && $node->name instanceof Identifier) {
            return $scope->resolveName($node->class) === Validator::class
                && in_array($node->name->toString(), ['make', 'validate'], true);
        }

        return $node instanceof FuncCall
            && $node->name instanceof Name
            && strtolower($node->name->toString()) === 'validator';
    }
}
