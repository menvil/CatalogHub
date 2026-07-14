<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid', 'site_id', 'job_type', 'status', 'target_type', 'target_id', 'locale',
    'requested_by_user_id', 'payload_json', 'attempts', 'started_at', 'finished_at',
    'failed_at', 'failure_reason',
])]
final class ProjectionJob extends Model
{
    protected static function booted(): void
    {
        self::creating(function (self $job): void {
            $job->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'target_id' => 'integer',
            'payload_json' => 'array',
            'attempts' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return HasMany<ProjectionLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(ProjectionLog::class);
    }
}
