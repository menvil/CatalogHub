<?php

namespace App\Services\Content;

use App\Data\Content\RelatedContentData;
use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Enums\ContentRelationTargetType;
use App\Models\ContentTranslation;
use App\Models\Site;
use Illuminate\Support\Collection;

final readonly class RelatedContentResolver
{
    public function __construct(private LocalizedUrlResolver $urls) {}

    /** @return Collection<int, RelatedContentData> */
    public function resolve(
        Site $site,
        string $locale,
        ContentRelationTargetType $relatedType,
        int $relatedId,
        int $limit = 4,
    ): Collection {
        return ContentTranslation::query()
            ->select('content_translations.*')
            ->join('content_items', 'content_items.id', '=', 'content_translations.content_item_id')
            ->join('content_relations', 'content_relations.content_item_id', '=', 'content_items.id')
            ->where('content_items.site_id', $site->id)
            ->where('content_items.status', 'published')
            ->where('content_translations.locale', $locale)
            ->where('content_translations.status', 'published')
            ->where('content_relations.related_type', $relatedType->value)
            ->where('content_relations.related_id', $relatedId)
            ->orderBy('content_relations.position')
            ->orderByDesc('content_items.published_at')
            ->orderBy('content_translations.id')
            ->limit(max(1, min($limit, 20)))
            ->with('contentItem')
            ->get()
            ->map(fn (ContentTranslation $translation): RelatedContentData => new RelatedContentData(
                typeLabel: $translation->contentItem->type->label(),
                title: $translation->title,
                excerpt: filled($translation->excerpt) ? (string) $translation->excerpt : null,
                url: $this->urls->article($site, $locale, $translation->slug),
                publishedDate: $translation->contentItem->published_at?->toFormattedDateString(),
            ));
    }
}
