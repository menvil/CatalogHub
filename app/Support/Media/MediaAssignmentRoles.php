<?php

namespace App\Support\Media;

final class MediaAssignmentRoles
{
    /** @var list<string> */
    public const ALL = ['main', 'card', 'gallery', 'hero', 'og', 'logo', 'icon', 'manual', 'package', 'technical'];

    public const LOCALE_PATTERN = '/^[a-z]{2,3}(-[A-Z]{2})?$/';

    /** @var list<string> */
    private const SINGULAR = ['main', 'card', 'hero', 'og', 'logo', 'icon'];

    public static function isSingular(string $role): bool
    {
        return in_array($role, self::SINGULAR, true);
    }
}
