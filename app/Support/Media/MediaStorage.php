<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class MediaStorage
{
    public function put(string $relativePath, string $contents): string
    {
        $path = $this->path($relativePath);

        Storage::disk($this->disk())->put($path, $contents);

        return $path;
    }

    public function disk(): string
    {
        return config('cataloghub_media.disk', 'media');
    }

    private function path(string $relativePath): string
    {
        $relativePath = trim($relativePath, '/');

        if ($relativePath === '') {
            throw new InvalidArgumentException('Media path cannot be empty.');
        }

        $prefix = trim((string) config('cataloghub_media.path_prefix', ''), '/');

        if ($prefix === '') {
            return $relativePath;
        }

        if ($relativePath === $prefix || str_starts_with($relativePath, "{$prefix}/")) {
            return $relativePath;
        }

        return "{$prefix}/{$relativePath}";
    }
}
