<?php

namespace App\Domains\PublicSite;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\Themes\DTO\RenderedBlock;
use App\Domains\Themes\Services\TemplateSlotRenderer;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

final readonly class HomepageBlockRenderer
{
    public function __construct(
        private TemplateSlotRenderer $templates,
        private LocalizedUrlResolver $urls,
    ) {}

    /**
     * @return Collection<int, array{
     *     code: string,
     *     view: string,
     *     config: array<string, mixed>,
     *     data: array<string, mixed>,
     *     position: int
     * }>
     */
    public function render(Site $site, string $locale): Collection
    {
        return $this->templates->blocksFor($site, 'home')
            ->map(function (RenderedBlock $block) use ($site, $locale): ?array {
                $view = 'public.blocks.'.str_replace('_', '-', $block->code);

                if (! View::exists($view)) {
                    Log::warning('Skipping public homepage block without a public view.', [
                        'site_id' => $site->getKey(),
                        'block_code' => $block->code,
                        'view' => $view,
                    ]);

                    return null;
                }

                return [
                    'code' => $block->code,
                    'view' => $view,
                    'config' => $block->config,
                    'data' => $this->dataFor($site, $locale, $block),
                    'position' => $block->position,
                ];
            })
            ->filter()
            ->values();
    }

    /** @return array<string, mixed> */
    private function dataFor(Site $site, string $locale, RenderedBlock $block): array
    {
        $limit = max(1, min((int) ($block->config['limit'] ?? 8), 24));

        return match ($block->code) {
            'popular_categories' => [
                'categories' => SiteCategoryProjection::query()
                    ->where('site_id', $site->id)
                    ->where('locale', $locale)
                    ->where('status', ProjectionStatus::Active)
                    ->orderBy('title')
                    ->limit($limit)
                    ->get()
                    ->map(fn (SiteCategoryProjection $category): array => [
                        'title' => $category->title,
                        'slug' => $category->slug,
                        'url' => $this->urls->category($site, $locale, $category),
                        'description' => data_get($category->payload_json, 'category.description'),
                        'image' => data_get($category->payload_json, 'category.image', data_get($category->payload_json, 'image')),
                    ])
                    ->all(),
            ],
            'top_products' => [
                'products' => SiteProductProjection::query()
                    ->where('site_id', $site->id)
                    ->where('locale', $locale)
                    ->where('status', ProjectionStatus::Active)
                    ->latest('built_at')
                    ->limit($limit)
                    ->get()
                    ->map(fn (SiteProductProjection $product): array => [
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'url' => $this->urls->product($site, $locale, $product),
                        'media' => $product->media_json ?? [],
                        'summary' => $product->search_summary_json ?? [],
                    ])
                    ->all(),
            ],
            'hero_search' => ['search_url' => $this->urls->search($site, $locale)],
            default => [],
        };
    }
}
