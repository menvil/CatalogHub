<?php

namespace App\Domains\Themes\Actions;

use App\Domains\Themes\Services\ThemeFeatureCompatibilityChecker;
use App\Exceptions\Themes\CannotActivateThemeException;
use App\Models\Site;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;

final class ActivateThemeAction
{
    public function __construct(private readonly ThemeFeatureCompatibilityChecker $compatibility) {}

    public function handle(Site $site, Theme $theme): void
    {
        if (! $theme->isActive()) {
            throw CannotActivateThemeException::inactive($theme->code);
        }

        $result = $this->compatibility->check($site, $theme);
        if (! $result->compatible) {
            throw CannotActivateThemeException::incompatible($theme->code, $result->missingFeatures);
        }

        DB::transaction(function () use ($site, $theme): void {
            $lockedSite = Site::query()->lockForUpdate()->findOrFail($site->getKey());
            $lockedSite->forceFill(['theme_id' => $theme->getKey()])->save();
        });

        $site->setAttribute('theme_id', $theme->getKey());
    }
}
