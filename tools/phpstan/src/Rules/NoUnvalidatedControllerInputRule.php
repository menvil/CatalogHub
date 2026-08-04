<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

use Illuminate\Http\Request;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

/** @implements Rule<Node> */
final class NoUnvalidatedControllerInputRule implements Rule
{
    /** @var array<string, true> */
    private array $reported = [];

    /** @var list<string> */
    private const RESTRICTED_METHODS = [
        'all',
        'anyFilled',
        'array',
        'boolean',
        'collect',
        'date',
        'enum',
        'enums',
        'except',
        'exists',
        'file',
        'files',
        'filled',
        'float',
        'get',
        'has',
        'hasAny',
        'input',
        'integer',
        'isNotFilled',
        'missing',
        'only',
        'post',
        'query',
        'string',
    ];

    public function getNodeType(): string
    {
        return Node::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! ArchitectureScope::isController($scope)) {
            return [];
        }

        if ($node instanceof ArrayDimFetch && $this->isRequest($scope, $node->var)) {
            return $this->errorsOnce($node, $scope, 'Controllers must read input through a dedicated Form Request typed accessor; Request array access is forbidden.');
        }

        if ($node instanceof PropertyFetch && $this->isRequest($scope, $node->var)) {
            return $this->errorsOnce($node, $scope, 'Controllers must read input through a dedicated Form Request typed accessor; Request magic properties are forbidden.');
        }

        if ($node instanceof FuncCall
            && $node->name instanceof Name
            && strtolower($node->name->toString()) === 'request'
            && $node->args !== []) {
            return $this->errorsOnce($node, $scope, 'Controllers must read input through a dedicated Form Request typed accessor; request() input access is forbidden.');
        }

        if (! ($node instanceof MethodCall || $node instanceof NullsafeMethodCall)
            || ! $node->name instanceof Identifier
            || ! in_array($node->name->toString(), self::RESTRICTED_METHODS, true)
            || ! $this->isRequest($scope, $node->var)) {
            return [];
        }

        return $this->errorsOnce($node, $scope, sprintf(
            'Controllers must read input through a dedicated Form Request typed accessor; Request::%s() is forbidden.',
            $node->name->toString(),
        ));
    }

    private function isRequest(Scope $scope, Expr $expression): bool
    {
        $type = TypeCombinator::removeNull($scope->getType($expression));

        return (new ObjectType(Request::class))->isSuperTypeOf($type)->yes();
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
                ->identifier('cataloghub.controller.unvalidatedInput')
                ->build(),
        ];
    }
}
