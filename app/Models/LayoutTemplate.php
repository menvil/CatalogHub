<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'theme_id',
    'page_type',
    'code',
    'name',
    'view_path',
    'slots_json',
    'config_schema_json',
    'status',
])]
final class LayoutTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'slots_json' => 'array',
            'config_schema_json' => 'array',
        ];
    }

    /** @return BelongsTo<Theme, $this> */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
