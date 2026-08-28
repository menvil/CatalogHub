<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaDeliveryState: string
{
    case Missing = 'missing';
    case Ready = 'ready';
    case Processing = 'processing';
    case Failed = 'failed';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Missing => 'Not assigned',
            self::Ready => 'Ready',
            self::Processing => 'Processing',
            self::Failed => 'Failed',
            self::Unavailable => 'Unavailable',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Ready => 'success',
            self::Processing => 'warning',
            self::Failed => 'danger',
            self::Missing, self::Unavailable => 'neutral',
        };
    }
}
