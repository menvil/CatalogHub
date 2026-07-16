<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Rules;

use PHPStan\Analyser\Scope;

final class ArchitectureScope
{
    private const CONTROLLER_NAMESPACE = 'App\\Http\\Controllers\\';

    public static function isController(Scope $scope): bool
    {
        $class = $scope->getClassReflection();

        return $class !== null && str_starts_with($class->getName(), self::CONTROLLER_NAMESPACE);
    }

    public static function isApplicationCode(Scope $scope): bool
    {
        $class = $scope->getClassReflection();

        return $class !== null && str_starts_with($class->getName(), 'App\\');
    }
}
