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
use Illuminate\Support\Facades\Log;

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

        $site->loadMissing('theme.manifest');
        $theme = $site->theme;
        if ($theme === null || ! $theme->isActive()) {
            return collect();
        }

        try {
            $manifest = $this->themes->manifestFor($theme);
        } catch (InvalidThemeManifestException $exception) {
            Log::warning('Cannot resolve homepage blocks for an invalid theme manifest.', [
                'site_id' => $site->getKey(),
                'theme_id' => $theme->getKey(),
                'exception' => $exception,
            ]);

            return collect();
        }

        /** @var list<string> $enabledFeatures */
        $enabledFeatures = $site->features()->where('is_enabled', true)->pluck('feature_key')->all();

        return $site->homeBlocks()
            ->with('definition')
            ->where('enabled', true)
            ->orderBy('position')
            ->get()
            ->map(function (SiteHomeBlock $homeBlock) use ($site, $pageType, $theme, $manifest, $enabledFeatures): ?RenderedBlock {
                try {
                    $definition = $homeBlock->definition;
                    if (! $definition instanceof BlockDefinition) {
                        throw CannotUseBlockException::because("Block {$homeBlock->block_code} is not registered.");
                    }

                    $this->compatibility->validateResolved($definition, $theme, $manifest, $enabledFeatures, $pageType);

                    if (! is_string($definition->view_component) || $definition->view_component === '') {
                        throw CannotUseBlockException::because("Block {$homeBlock->block_code} does not declare a view component.");
                    }

                    return new RenderedBlock(
                        code: $homeBlock->block_code,
                        viewComponent: $definition->view_component,
                        config: $homeBlock->config_json ?? [],
                        position: $homeBlock->position,
                    );
                } catch (CannotUseBlockException $exception) {
                    Log::warning('Skipping incompatible site home block.', [
                        'site_id' => $site->getKey(),
                        'block_code' => $homeBlock->block_code,
                        'page_type' => $pageType,
                        'exception' => $exception,
                    ]);

                    return null;
                }
            })
            ->filter(fn (?RenderedBlock $block): bool => $block instanceof RenderedBlock)
            ->values();
    }

    public function resolveLayout(Site $site, string $pageType): ?LayoutTemplate
    {
        $site->loadMissing('theme.manifest');
        $theme = $site->theme;
        if ($theme === null || ! $theme->isActive()) {
            return null;
        }

        try {
            $layoutCode = $this->themes->manifestFor($theme)->layoutFor($pageType);
        } catch (InvalidThemeManifestException $exception) {
            Log::warning('Cannot resolve layout for an invalid theme manifest.', [
                'site_id' => $site->getKey(),
                'theme_id' => $theme->getKey(),
                'page_type' => $pageType,
                'exception' => $exception,
            ]);

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
