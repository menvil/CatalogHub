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

    public static function isPresentationLayer(Scope $scope): bool
    {
        $class = $scope->getClassReflection();

        if ($class === null) {
            return false;
        }

        foreach ([
            'App\\Filament\\',
            'App\\Http\\Controllers\\',
            'App\\Http\\Requests\\',
            'App\\Livewire\\',
        ] as $namespace) {
            if (str_starts_with($class->getName(), $namespace)) {
                return true;
            }
        }

        return false;
    }

    public static function isApplicationCode(Scope $scope): bool
    {
        $class = $scope->getClassReflection();

        return $class !== null && str_starts_with($class->getName(), 'App\\');
    }

    public static function readOnlyLayer(Scope $scope): ?string
    {
        $class = $scope->getClassReflection()?->getName();

        if ($class === null) {
            return null;
        }

        return match (true) {
            str_starts_with($class, 'App\\Queries\\') => 'Query Objects',
            str_starts_with($class, 'App\\Policies\\') => 'Policies',
            default => null,
        };
    }
}
