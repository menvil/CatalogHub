<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

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
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

/** @implements Rule<CallLike> */
final class RestrictedDatabaseFacadeRule implements Rule
{
    /** @var array<string, true> */
    private array $reported = [];

    /** @var list<string> */
    private const TRANSACTION_METHODS = [
        'beginTransaction',
        'commit',
        'rollBack',
        'transaction',
    ];

    /** @var list<string> */
    private const LOW_LEVEL_METHODS = [
        'affectingStatement',
        'delete',
        'insert',
        'raw',
        'scalar',
        'select',
        'selectOne',
        'statement',
        'table',
        'unprepared',
        'update',
    ];

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (($node instanceof MethodCall || $node instanceof NullsafeMethodCall)
            && $node->name instanceof Identifier
            && in_array($node->name->toString(), self::TRANSACTION_METHODS, true)
            && ArchitectureScope::isPresentationLayer($scope)
            && (new ObjectType(ConnectionInterface::class))->isSuperTypeOf(
                TypeCombinator::removeNull($scope->getType($node->var)),
            )->yes()) {
            return $this->errorsOnce(
                $node,
                $scope,
                'Presentation classes must not manage database transactions; move orchestration to an Action or Service.',
                'cataloghub.presentation.transaction',
            );
        }

        if (! $node instanceof StaticCall
            || ! $node->class instanceof Name
            || ! $node->name instanceof Identifier
            || $scope->resolveName($node->class) !== DB::class) {
            return [];
        }

        $method = $node->name->toString();
        if (in_array($method, self::TRANSACTION_METHODS, true)
            && ArchitectureScope::isPresentationLayer($scope)) {
            return $this->errorsOnce(
                $node,
                $scope,
                'Presentation classes must not manage database transactions; move orchestration to an Action or Service.',
                'cataloghub.presentation.transaction',
            );
        }

        if (! ArchitectureScope::isApplicationCode($scope)
            || ! in_array($method, self::LOW_LEVEL_METHODS, true)) {
            return [];
        }

        return $this->errorsOnce(
            $node,
            $scope,
            'Low-level DB facade queries must be replaced with Eloquent or isolated in an approved Query Object.',
            'cataloghub.database.restrictedFacade',
        );
    }

    /** @return list<RuleError> */
    private function errorsOnce(CallLike $node, Scope $scope, string $message, string $identifier): array
    {
        $key = implode(':', [$scope->getFile(), (string) $node->getStartLine(), $identifier]);

        if (isset($this->reported[$key])) {
            return [];
        }

        $this->reported[$key] = true;

        return [
            RuleErrorBuilder::message($message)
                ->identifier($identifier)
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
