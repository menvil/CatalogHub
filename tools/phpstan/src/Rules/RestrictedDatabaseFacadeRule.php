<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

use Illuminate\Support\Facades\DB;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<StaticCall> */
final class RestrictedDatabaseFacadeRule implements Rule
{
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
        return StaticCall::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->class instanceof Name
            || ! $node->name instanceof Identifier
            || $scope->resolveName($node->class) !== DB::class) {
            return [];
        }

        $method = $node->name->toString();
        if ($method === 'transaction' && ArchitectureScope::isController($scope)) {
            return [$this->error($node, 'Database transactions must be moved from controllers to an Action or Service.')];
        }

        if (! ArchitectureScope::isApplicationCode($scope)
            || ! in_array($method, self::LOW_LEVEL_METHODS, true)) {
            return [];
        }

        return [$this->error($node, 'Low-level DB facade queries must be replaced with Eloquent or isolated in an approved Query Object.')];
    }

    private function error(StaticCall $node, string $message): RuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier('cataloghub.database.restrictedFacade')
            ->line($node->getStartLine())
            ->build();
    }
}
