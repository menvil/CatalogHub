<?php

namespace App\Models;

use Database\Factories\ContentTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property list<array{question: string, answer: string, position?: int}>|null $body_json */
#[Fillable([
    'content_item_id', 'locale', 'slug', 'title', 'excerpt', 'body', 'body_json',
    'status', 'meta_title', 'meta_description', 'og_title', 'og_description', 'source_hash',
])]
final class ContentTranslation extends Model
{
    /** @use HasFactory<ContentTranslationFactory> */
    use HasFactory;

    protected static function newFactory(): ContentTranslationFactory
    {
        return ContentTranslationFactory::new();
    }

    protected function casts(): array
    {
        return ['body_json' => 'array'];
    }

    /** @return BelongsTo<ContentItem, $this> */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function seoTitle(): string
    {
        return filled($this->meta_title) ? (string) $this->meta_title : (string) $this->title;
    }

    public function seoDescription(): ?string
    {
        $description = filled($this->meta_description) ? $this->meta_description : $this->excerpt;

        return filled($description) ? (string) $description : null;
    }

    public function openGraphTitle(): string
    {
        return filled($this->og_title) ? (string) $this->og_title : $this->seoTitle();
    }

    public function openGraphDescription(): ?string
    {
        return filled($this->og_description) ? (string) $this->og_description : $this->seoDescription();
    }
}
