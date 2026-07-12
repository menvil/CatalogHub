<?php

namespace App\Services\Media;

use App\Models\MediaAsset;
use App\Models\MediaVariant;
use Illuminate\Support\Facades\Storage;

final class MediaUrlGenerator
{
    public function forAsset(MediaAsset $asset): string
    {
        return Storage::disk($asset->disk)->url($asset->original_path);
    }

    public function forVariant(MediaVariant $variant): string
    {
        return Storage::disk($variant->disk)->url($variant->path);
    }

    public function placeholder(string $role = 'default'): string
    {
        return (string) config('media.placeholder_url', '/images/media-placeholder.svg');
    }
}
