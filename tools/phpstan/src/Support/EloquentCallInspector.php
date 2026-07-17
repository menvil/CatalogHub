<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

final class EloquentCallInspector
{
    public static function hasModelOrQueryReceiver(
        Scope $scope,
        MethodCall|NullsafeMethodCall|StaticCall $node,
    ): bool {
        if ($node instanceof MethodCall || $node instanceof NullsafeMethodCall) {
            $type = TypeCombinator::removeNull($scope->getType($node->var));

            foreach ([Model::class, EloquentBuilder::class, QueryBuilder::class, Relation::class] as $class) {
                if ((new ObjectType($class))->isSuperTypeOf($type)->yes()) {
                    return true;
                }
            }

            return false;
        }

        if (! $node->class instanceof Name) {
            return false;
        }

        return (new ObjectType(Model::class))
            ->isSuperTypeOf($scope->resolveTypeByName($node->class))
            ->yes();
    }
}
