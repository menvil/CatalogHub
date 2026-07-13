<?php

namespace App\Domains\Themes\Services;

use App\Domains\Themes\DTO\ThemeCompatibilityResult;
use App\Exceptions\Themes\InvalidThemeManifestException;
use App\Models\Site;
use App\Models\Theme;

final class ThemeFeatureCompatibilityChecker
{
    /** @var array<string, list<string>> */
    private const FEATURE_CAPABILITIES = [
        'reviews' => ['review_form', 'latest_reviews'],
        'leads' => ['lead_form'],
        'price_comparison' => ['price_block'],
        'comparison' => ['comparison_block'],
        'polls' => ['poll_block'],
        'blog' => ['buying_guides'],
        'guides' => ['buying_guides'],
        'external_price_widget' => ['price_block'],
        'local_offers' => ['price_block'],
    ];

    public function __construct(private readonly ThemeRegistry $themes) {}

    public function check(Site $site, Theme $theme): ThemeCompatibilityResult
    {
        /** @var list<string> $enabledFeatures */
        $enabledFeatures = $site->features()
            ->where('is_enabled', true)
            ->orderBy('feature_key')
            ->pluck('feature_key')
            ->all();

        try {
            $manifest = $this->themes->manifestFor($theme);
        } catch (InvalidThemeManifestException $exception) {
            return new ThemeCompatibilityResult(
                false,
                $enabledFeatures,
                [$exception->getMessage()],
            );
        }

        $missing = [];
        foreach ($enabledFeatures as $feature) {
            $capabilities = self::FEATURE_CAPABILITIES[$feature] ?? [$feature];
            $supported = collect($capabilities)->contains(
                fn (string $capability): bool => $manifest->supports($capability)
            );

            if (! $supported) {
                $missing[] = $feature;
            }
        }

        return new ThemeCompatibilityResult($missing === [], $missing);
    }
}
