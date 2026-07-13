<?php

namespace App\Domains\Themes\Services;

use App\Domains\Themes\ValueObjects\ThemeManifest;
use App\Exceptions\Themes\InvalidThemeManifestException;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Illuminate\Database\Eloquent\Collection;

final class ThemeRegistry
{
    /** @return Collection<int, Theme> */
    public function activeThemes(): Collection
    {
        return Theme::query()->with('manifest')->active()->orderBy('name')->get();
    }

    public function findByCode(string $code): ?Theme
    {
        return Theme::query()->where('code', $code)->first();
    }

    public function manifestFor(Theme $theme): ThemeManifest
    {
        $record = $theme->manifest;

        if (! $record instanceof ThemeManifestRecord) {
            throw InvalidThemeManifestException::because("Theme {$theme->code} does not have a manifest.");
        }

        return ThemeManifest::fromArray($record->manifest_json);
    }

    public function themeSupports(Theme $theme, string $featureOrBlock): bool
    {
        return $this->manifestFor($theme)->supports($featureOrBlock);
    }
}
