<?php

namespace App\Domains\Themes\Services;

use App\Domains\Themes\DTO\RenderedBlock;
use App\Domains\Themes\DTO\RenderedTemplate;
use App\Exceptions\Themes\CannotUseBlockException;
use App\Exceptions\Themes\InvalidThemeManifestException;
use App\Models\BlockDefinition;
use App\Models\LayoutTemplate;
use App\Models\Site;
use App\Models\SiteHomeBlock;
use Illuminate\Support\Collection;

final class TemplateSlotRenderer
{
    public function __construct(
        private readonly ThemeRegistry $themes,
        private readonly BlockCompatibilityValidator $compatibility,
    ) {}

    public function renderHome(Site $site): RenderedTemplate
    {
        return new RenderedTemplate(
            pageType: 'home',
            layout: $this->resolveLayout($site, 'home'),
            blocks: $this->blocksFor($site, 'home'),
        );
    }

    /** @return Collection<int, RenderedBlock> */
    public function blocksFor(Site $site, string $pageType): Collection
    {
        if ($pageType !== 'home') {
            return collect();
        }

        return $site->homeBlocks()
            ->with('definition')
            ->where('enabled', true)
            ->orderBy('position')
            ->get()
            ->map(function (SiteHomeBlock $homeBlock) use ($site, $pageType): RenderedBlock {
                $this->compatibility->validate($site, $homeBlock->block_code, $pageType);
                $definition = $homeBlock->definition;

                if (! $definition instanceof BlockDefinition || ! is_string($definition->view_component) || $definition->view_component === '') {
                    throw CannotUseBlockException::because("Block {$homeBlock->block_code} does not declare a view component.");
                }

                return new RenderedBlock(
                    code: $homeBlock->block_code,
                    viewComponent: $definition->view_component,
                    config: $homeBlock->config_json ?? [],
                    position: $homeBlock->position,
                );
            })
            ->values();
    }

    public function resolveLayout(Site $site, string $pageType): ?LayoutTemplate
    {
        $theme = $site->theme()->first();
        if ($theme === null || ! $theme->isActive()) {
            return null;
        }

        try {
            $layoutCode = $this->themes->manifestFor($theme)->layoutFor($pageType);
        } catch (InvalidThemeManifestException) {
            return null;
        }

        if ($layoutCode === null) {
            return null;
        }

        return $theme->layoutTemplates()
            ->where('page_type', $pageType)
            ->where('code', $layoutCode)
            ->where('status', 'active')
            ->first();
    }
}
