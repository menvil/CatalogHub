<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusHelpers;

enum PriceSourceSyncStatus: string
{
    use HasStatusHelpers;

    case Queued = 'queued';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case PartiallyCompleted = 'partially_completed';

    public function color(): string
    {
        return match ($this) {
            self::Queued => 'gray',
            self::Running => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
            self::PartiallyCompleted => 'warning',
        };
    }
}
