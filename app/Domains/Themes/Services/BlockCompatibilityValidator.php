<?php

namespace App\Domains\Themes\Services;

use App\Domains\Themes\ValueObjects\ThemeManifest;
use App\Exceptions\Themes\CannotUseBlockException;
use App\Exceptions\Themes\InvalidThemeManifestException;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\Theme;

final class BlockCompatibilityValidator
{
    /** @var array<string, list<string>> */
    private const THEME_CAPABILITY_ALIASES = [
        'top_products' => ['top_products', 'product_grid'],
    ];

    /** @var array<string, list<string>> */
    private const FEATURE_ALTERNATIVES = [
        'buying_guides' => ['guides', 'blog'],
        'price_block' => ['price_comparison', 'local_offers', 'external_price_widget'],
    ];

    public function __construct(
        private readonly BlockRegistry $blocks,
        private readonly ThemeRegistry $themes,
    ) {}

    public function validate(Site $site, string $blockCode, string $pageType = 'home'): void
    {
        $block = $this->blocks->findByCode($blockCode);
        if (! $block instanceof BlockDefinition || ! $block->isActive()) {
            throw CannotUseBlockException::because("Block {$blockCode} is not registered as active.");
        }

        $theme = $site->theme()->first();
        if ($theme === null || ! $theme->isActive()) {
            throw CannotUseBlockException::because('The site does not have an active theme.');
        }

        try {
            $manifest = $this->themes->manifestFor($theme);
        } catch (InvalidThemeManifestException $exception) {
            throw CannotUseBlockException::because($exception->getMessage(), $exception);
        }

        /** @var list<string> $enabledFeatures */
        $enabledFeatures = $site->features()->where('is_enabled', true)->pluck('feature_key')->all();

        $this->validateResolved($block, $theme, $manifest, $enabledFeatures, $pageType);
    }

    /** @param list<string> $enabledFeatures */
    public function validateResolved(
        BlockDefinition $block,
        Theme $theme,
        ThemeManifest $manifest,
        array $enabledFeatures,
        string $pageType = 'home',
    ): void {
        if (! $block->isActive()) {
            throw CannotUseBlockException::because("Block {$block->code} is not registered as active.");
        }

        if (! $block->supportsPage($pageType)) {
            throw CannotUseBlockException::because("Block {$block->code} does not support page type {$pageType}.");
        }

        if (! $theme->isActive()) {
            throw CannotUseBlockException::because("Theme {$theme->code} is not active.");
        }

        $capabilities = self::THEME_CAPABILITY_ALIASES[$block->code] ?? [$block->code];
        if (! collect($capabilities)->contains(fn (string $capability): bool => $manifest->supports($capability))) {
            throw CannotUseBlockException::because("Theme {$theme->code} does not support block {$block->code}.");
        }

        $featureAlternatives = self::FEATURE_ALTERNATIVES[$block->code] ?? null;

        if ($featureAlternatives !== null && empty(array_intersect($featureAlternatives, $enabledFeatures))) {
            throw CannotUseBlockException::because("Block {$block->code} requires one of: ".implode(', ', $featureAlternatives).'.');
        }

        foreach ($block->required_features_json ?? [] as $requiredFeature) {
            if ($featureAlternatives !== null && in_array($requiredFeature, $featureAlternatives, true)) {
                continue;
            }

            if (! in_array($requiredFeature, $enabledFeatures, true)) {
                throw CannotUseBlockException::because("Block {$block->code} requires enabled feature {$requiredFeature}.");
            }
        }
    }
}
