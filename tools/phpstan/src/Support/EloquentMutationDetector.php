<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Support;

use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;

final class EloquentMutationDetector
{
    /** @var list<string> */
    private const METHODS = [
        'associate',
        'attach',
        'create',
        'createMany',
        'createManyQuietly',
        'decrement',
        'delete',
        'deleteQuietly',
        'destroy',
        'detach',
        'dissociate',
        'fill',
        'firstOrCreate',
        'forceCreate',
        'forceCreateQuietly',
        'forceDelete',
        'forceDeleteQuietly',
        'forceFill',
        'increment',
        'insert',
        'insertGetId',
        'insertOrIgnore',
        'push',
        'pushQuietly',
        'restore',
        'restoreQuietly',
        'save',
        'saveMany',
        'saveManyQuietly',
        'saveOrFail',
        'saveQuietly',
        'sync',
        'syncWithPivotValues',
        'syncWithoutDetaching',
        'toggle',
        'touch',
        'touchQuietly',
        'update',
        'updateExistingPivot',
        'updateOrCreate',
        'updateOrInsert',
        'updateQuietly',
        'upsert',
    ];

    public static function mutationMethod(Scope $scope, CallLike $node): ?string
    {
        if (! ($node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof StaticCall)
            || ! $node->name instanceof Identifier
            || ! in_array($node->name->toString(), self::METHODS, true)
            || ! EloquentCallInspector::hasModelOrQueryReceiver($scope, $node)) {
            return null;
        }

        return $node->name->toString();
    }
}
