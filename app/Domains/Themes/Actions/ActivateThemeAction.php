<?php

namespace App\Domains\Themes\Actions;

use App\Domains\Themes\Services\BlockCompatibilityValidator;
use App\Domains\Themes\Services\ThemeFeatureCompatibilityChecker;
use App\Domains\Themes\Services\ThemeRegistry;
use App\Exceptions\Themes\CannotActivateThemeException;
use App\Exceptions\Themes\CannotUseBlockException;
use App\Exceptions\Themes\InvalidThemeManifestException;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;

final class ActivateThemeAction
{
    public function __construct(
        private readonly ThemeFeatureCompatibilityChecker $compatibility,
        private readonly ThemeRegistry $themes,
        private readonly BlockCompatibilityValidator $blocks,
    ) {}

    public function handle(Site $site, Theme $theme): void
    {
        DB::transaction(function () use ($site, $theme): void {
            $lockedSite = Site::query()->lockForUpdate()->findOrFail($site->getKey());
            $lockedTheme = Theme::query()->with('manifest')->lockForUpdate()->findOrFail($theme->getKey());

            if (! $lockedTheme->isActive()) {
                throw CannotActivateThemeException::inactive($lockedTheme->code);
            }

            try {
                $manifest = $this->themes->manifestFor($lockedTheme);
            } catch (InvalidThemeManifestException $exception) {
                throw CannotActivateThemeException::invalidManifest($lockedTheme->code, $exception);
            }

            $enabledFeatures = $this->compatibility->enabledFeaturesFor($lockedSite);
            $result = $this->compatibility->checkWithManifest($enabledFeatures, $manifest);
            if (! $result->compatible) {
                throw CannotActivateThemeException::incompatible($lockedTheme->code, $result->missingFeatures);
            }

            $homeBlocks = $lockedSite->homeBlocks()->with('definition')->where('enabled', true)->lockForUpdate()->get();
            foreach ($homeBlocks as $homeBlock) {
                $definition = $homeBlock->definition;

                if (! $definition instanceof BlockDefinition) {
                    $previous = CannotUseBlockException::because("Block {$homeBlock->block_code} is not registered.");
                    throw CannotActivateThemeException::incompatibleBlock($lockedTheme->code, $homeBlock->block_code, $previous);
                }

                try {
                    $this->blocks->validateResolved($definition, $lockedTheme, $manifest, $enabledFeatures);
                } catch (CannotUseBlockException $exception) {
                    throw CannotActivateThemeException::incompatibleBlock($lockedTheme->code, $homeBlock->block_code, $exception);
                }
            }

            $lockedSite->forceFill(['theme_id' => $lockedTheme->getKey()])->save();
        });

        $site->setAttribute('theme_id', $theme->getKey());
    }
}
