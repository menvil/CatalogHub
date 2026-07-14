<?php

namespace App\Services\Content;

use App\Data\Content\RelatedContentData;
use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Enums\ContentRelationTargetType;
use App\Enums\ContentType;
use App\Models\ContentTranslation;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class HomepageContentResolver
{
    public function __construct(private LocalizedUrlResolver $urls) {}

    /**
     * @param  list<ContentType>  $types
     * @return Collection<int, RelatedContentData>
     */
    public function resolve(
        Site $site,
        string $locale,
        array $types = [],
        int $limit = 6,
        ?int $categoryId = null,
    ): Collection {
        $query = ContentTranslation::query()
            ->select('content_translations.*')
            ->join('content_items', 'content_items.id', '=', 'content_translations.content_item_id')
            ->where('content_items.site_id', $site->id)
            ->where('content_items.status', 'published')
            ->where('content_translations.locale', $locale)
            ->where('content_translations.status', 'published')
            ->when(
                $types !== [],
                fn (Builder $builder): Builder => $builder->whereIn(
                    'content_items.type',
                    array_map(fn (ContentType $type): string => $type->value, $types),
                ),
            )
            ->when($categoryId !== null, fn (Builder $builder): Builder => $builder->whereExists(
                fn ($relation) => $relation
                    ->selectRaw('1')
                    ->from('content_relations')
                    ->whereColumn('content_relations.content_item_id', 'content_items.id')
                    ->where('content_relations.related_type', ContentRelationTargetType::Category->value)
                    ->where('content_relations.related_id', $categoryId),
            ))
            ->orderByDesc('content_items.published_at')
            ->orderByDesc('content_items.id')
            ->limit(max(1, min($limit, 20)))
            ->with('contentItem');

        return $query->get()->map(fn (ContentTranslation $translation): RelatedContentData => new RelatedContentData(
            typeLabel: $translation->contentItem->type->label(),
            title: $translation->title,
            excerpt: filled($translation->excerpt) ? (string) $translation->excerpt : null,
            url: $this->urls->article($site, $locale, $translation->slug),
            publishedDate: $translation->contentItem->published_at?->toFormattedDateString(),
        ));
    }
}
