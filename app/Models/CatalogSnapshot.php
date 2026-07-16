<?php

namespace App\Models;

use Database\Factories\CatalogSnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property array<string, array<string, mixed>>|null $files_json
 * @property array<string, mixed>|null $metadata_json
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $failed_at
 */
#[Fillable([
    'uuid',
    'status',
    'snapshot_type',
    'storage_disk',
    'storage_path',
    'files_json',
    'metadata_json',
    'started_at',
    'completed_at',
    'failed_at',
    'failure_reason',
    'created_by_user_id',
])]
final class CatalogSnapshot extends Model
{
    /** @use HasFactory<CatalogSnapshotFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (CatalogSnapshot $snapshot): void {
            $snapshot->uuid ??= (string) Str::uuid();
        });
    }

    protected static function newFactory(): CatalogSnapshotFactory
    {
        return CatalogSnapshotFactory::new();
    }

    protected function casts(): array
    {
        return [
            'files_json' => 'array',
            'metadata_json' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<MediaManifest, $this> */
    public function mediaManifests(): HasMany
    {
        return $this->hasMany(MediaManifest::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markGenerating(): self
    {
        $this->forceFill([
            'status' => 'generating',
            'started_at' => $this->started_at ?? now(),
            'completed_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
        ])->save();

        return $this;
    }

    /** @param array<string, mixed>|null $files */
    public function markCompleted(?array $files = null): self
    {
        $this->forceFill([
            'status' => 'completed',
            'files_json' => $files ?? $this->files_json,
            'completed_at' => now(),
            'failed_at' => null,
            'failure_reason' => null,
        ])->save();

        return $this;
    }

    public function markFailed(string $reason): self
    {
        $this->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
            'completed_at' => null,
        ])->save();

        return $this;
    }
}
