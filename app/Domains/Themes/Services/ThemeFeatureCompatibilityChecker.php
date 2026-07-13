<?php

namespace App\Domains\Themes\Services;

use App\Domains\Themes\DTO\ThemeCompatibilityResult;
use App\Domains\Themes\ValueObjects\ThemeManifest;
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
        $enabledFeatures = $this->enabledFeaturesFor($site);

        try {
            $manifest = $this->themes->manifestFor($theme);
        } catch (InvalidThemeManifestException $exception) {
            return new ThemeCompatibilityResult(
                false,
                $enabledFeatures,
                [$exception->getMessage()],
            );
        }

        return $this->checkWithManifest($enabledFeatures, $manifest);
    }

    /** @return list<string> */
    public function enabledFeaturesFor(Site $site): array
    {
        /** @var list<string> $features */
        $features = $site->features()
            ->where('is_enabled', true)
            ->orderBy('feature_key')
            ->pluck('feature_key')
            ->all();

        return $features;
    }

    /** @param list<string> $enabledFeatures */
    public function checkWithManifest(array $enabledFeatures, ThemeManifest $manifest): ThemeCompatibilityResult
    {
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
