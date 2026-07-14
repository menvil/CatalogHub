<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'site_id', 'locale', 'entity_type', 'entity_id', 'conflict_type', 'severity', 'status',
    'message', 'context_json', 'first_seen_at', 'last_seen_at', 'resolved_at',
])]
final class ProjectionConflict extends Model
{
    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'context_json' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
