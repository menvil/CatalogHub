<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'content_item_id', 'locale', 'slug', 'title', 'excerpt', 'body', 'body_json',
    'status', 'meta_title', 'meta_description', 'og_title', 'og_description', 'source_hash',
])]
final class ContentTranslation extends Model
{
    protected function casts(): array
    {
        return ['body_json' => 'array'];
    }

    /** @return BelongsTo<ContentItem, $this> */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }
}
