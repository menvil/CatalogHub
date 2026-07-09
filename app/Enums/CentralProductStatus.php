<?php

namespace App\Enums;

enum CentralProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public static function default(): self
    {
        return self::Draft;
    }
}
