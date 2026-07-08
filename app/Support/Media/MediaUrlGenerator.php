<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class MediaUrlGenerator
{
    public function url(string $relativePath): string
    {
        $relativePath = trim($relativePath, '/');

        if ($relativePath === '') {
            throw new InvalidArgumentException('Media path cannot be empty.');
        }

        return Storage::disk($this->disk())->url($relativePath);
    }

    public function disk(): string
    {
        return config('cataloghub_media.url_disk', 'public_media');
    }
}
