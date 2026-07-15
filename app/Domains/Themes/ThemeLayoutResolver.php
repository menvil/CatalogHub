<?php

namespace App\Domains\Themes;

use App\Domains\Themes\Services\TemplateSlotRenderer;
use App\Exceptions\Themes\CannotResolveThemeLayoutException;
use App\Models\LayoutTemplate;
use App\Models\Site;

final readonly class ThemeLayoutResolver
{
    /** @var array<string, string> */
    private const DEFAULT_LAYOUTS = [
        'home' => 'public.pages.home',
        'category' => 'public.pages.category',
        'listing' => 'public.pages.listing',
        'product' => 'public.pages.product',
        'compare' => 'public.pages.compare',
        'article' => 'public.content.show',
        'search' => 'public.pages.search',
    ];

    public function __construct(private TemplateSlotRenderer $templates) {}

    public function resolve(Site $site, string $pageType, ?string $layoutKey = null): string
    {
        if (! array_key_exists($pageType, self::DEFAULT_LAYOUTS)) {
            throw CannotResolveThemeLayoutException::unsupportedPageType($pageType);
        }

        $site->loadMissing('theme.manifest');

        if ($layoutKey !== null && $site->theme?->isActive()) {
            $layout = $site->theme->layoutTemplates()
                ->where('page_type', $pageType)
                ->where('code', $layoutKey)
                ->where('status', 'active')
                ->first();

            if ($layout instanceof LayoutTemplate) {
                return $layout->view_path;
            }

            return self::DEFAULT_LAYOUTS[$pageType];
        }

        $layout = $this->templates->resolveLayout($site, $pageType);

        return $layout->view_path ?? self::DEFAULT_LAYOUTS[$pageType];
    }
}
