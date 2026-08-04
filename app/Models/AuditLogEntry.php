<?php

namespace App\Models;

use Database\Factories\AuditLogEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * @property array<string, mixed>|null $before_json
 * @property array<string, mixed>|null $after_json
 */
#[Fillable([
    'actor_id',
    'context',
    'site_id',
    'action',
    'subject_type',
    'subject_id',
    'before_json',
    'after_json',
    'request_id',
])]
final class AuditLogEntry extends Model
{
    /** @use HasFactory<AuditLogEntryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function newFactory(): AuditLogEntryFactory
    {
        return AuditLogEntryFactory::new();
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Audit log entries are append-only.');
        });
        self::deleting(static function (): never {
            throw new LogicException('Audit log entries are append-only.');
        });
    }

    protected function casts(): array
    {
        return [
            'before_json' => 'array',
            'after_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
