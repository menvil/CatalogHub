<?php

namespace App\Enums;

use App\Enums\Concerns\HasStatusHelpers;

enum ExternalProductMappingStatus: string
{
    use HasStatusHelpers;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Ignored = 'ignored';

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Ignored => 'gray',
        };
    }
}
