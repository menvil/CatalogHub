<?php

namespace App\Domains\Themes\Services;

use App\Domains\Themes\ValueObjects\ThemeManifest;
use App\Exceptions\Themes\InvalidThemeManifestException;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Illuminate\Support\Facades\DB;
use JsonException;

final class ThemeManifestParser
{
    /** @param array<string, mixed>|string $manifest */
    public function parseAndStore(Theme $theme, array|string $manifest): ThemeManifest
    {
        $data = is_string($manifest) ? $this->decode($manifest) : $manifest;

        return DB::transaction(function () use ($data, $theme): ThemeManifest {
            $lockedTheme = Theme::query()->whereKey($theme->getKey())->lockForUpdate()->firstOrFail();
            $value = ThemeManifest::fromArray($data);

            if ($value->code !== $lockedTheme->code) {
                throw InvalidThemeManifestException::because('Theme manifest code must match the theme code.');
            }

            $schemaVersion = $data['schema_version'] ?? null;
            if ($schemaVersion !== null && (! is_string($schemaVersion) || trim($schemaVersion) === '')) {
                throw InvalidThemeManifestException::because('Theme manifest schema_version must be a non-empty string when provided.');
            }

            ThemeManifestRecord::query()->updateOrCreate(
                ['theme_id' => $lockedTheme->getKey()],
                [
                    'manifest_json' => $data,
                    'supports_json' => $value->supports,
                    'layouts_json' => $value->layouts,
                    'schema_version' => $schemaVersion,
                    'validated_at' => now(),
                    'validation_errors_json' => null,
                ],
            );

            return $value;
        });
    }

    /** @return array<string, mixed> */
    private function decode(string $manifest): array
    {
        try {
            $decoded = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidThemeManifestException::because('Theme manifest JSON is invalid.', $exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw InvalidThemeManifestException::because('Theme manifest JSON must decode to an object.');
        }

        return $decoded;
    }
}
