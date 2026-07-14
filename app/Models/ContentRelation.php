<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'content_item_id', 'related_type', 'related_id', 'relation_type', 'position', 'metadata',
])]
final class ContentRelation extends Model
{
    protected function casts(): array
    {
        return [
            'related_id' => 'integer',
            'position' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<ContentItem, $this> */
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }
}
