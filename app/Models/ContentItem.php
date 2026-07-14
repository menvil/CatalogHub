<?php

namespace App\Models;

use Database\Factories\ContentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** @property array<string, mixed>|null $metadata */
#[Fillable([
    'site_id', 'type', 'status', 'published_at', 'archived_at',
    'created_by_user_id', 'updated_by_user_id', 'metadata',
])]
final class ContentItem extends Model
{
    /** @use HasFactory<ContentItemFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): ContentItemFactory
    {
        return ContentItemFactory::new();
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @param Builder<ContentItem> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** @param Builder<ContentItem> $query */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /** @param Builder<ContentItem> $query */
    public function scopeForSite(Builder $query, Site|int $site): Builder
    {
        return $query->where('site_id', $site instanceof Site ? $site->getKey() : $site);
    }

    /** @param Builder<ContentItem> $query */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return HasMany<ContentTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(ContentTranslation::class);
    }

    /** @return HasMany<ContentRelation, $this> */
    public function relations(): HasMany
    {
        return $this->hasMany(ContentRelation::class)->orderBy('position')->orderBy('id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
