<?php

namespace App\Enums;

enum SyncConflictStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
    case Ignored = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Resolved => 'Resolved',
            self::Ignored => 'Ignored',
        };
    }
}
