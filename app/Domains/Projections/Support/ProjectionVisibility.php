<?php

namespace App\Domains\Projections\Support;

final class ProjectionVisibility
{
    public static function isVisible(mixed $visibility): bool
    {
        if (is_bool($visibility)) {
            return $visibility;
        }

        return ! in_array(
            mb_strtolower((string) $visibility),
            ['0', 'false', 'hidden', 'disabled', 'inactive'],
            true,
        );
    }
}
