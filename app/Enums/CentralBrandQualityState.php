<?php

declare(strict_types=1);

namespace App\Enums;

enum CentralBrandQualityState: string
{
    case Complete = 'complete';
    case NeedsAttention = 'needs_attention';

    public function label(): string
    {
        return match ($this) {
            self::Complete => 'Complete',
            self::NeedsAttention => 'Needs attention',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Complete => 'success',
            self::NeedsAttention => 'warning',
        };
    }
}
