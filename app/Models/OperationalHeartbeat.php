<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'last_ran_at'])]
final class OperationalHeartbeat extends Model
{
    protected function casts(): array
    {
        return [
            'last_ran_at' => 'datetime',
        ];
    }
}
