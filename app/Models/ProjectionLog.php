<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'projection_job_id', 'site_id', 'level', 'event', 'message', 'context_json',
    'entity_type', 'entity_id',
])]
final class ProjectionLog extends Model
{
    /** @var array<string, string> */
    public const EVENT_OPTIONS = [
        'started' => 'Started',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'stale' => 'Stale',
        'item_failed' => 'Item failed',
    ];

    /** @var array<string, string> */
    public const ENTITY_TYPE_OPTIONS = [
        'product' => 'Product',
        'category' => 'Category',
        'site' => 'Site',
    ];

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'context_json' => 'array',
            'entity_id' => 'integer',
        ];
    }

    /** @return BelongsTo<ProjectionJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(ProjectionJob::class, 'projection_job_id');
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
