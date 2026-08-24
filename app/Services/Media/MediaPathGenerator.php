<?php

namespace App\Services\Media;

final class MediaPathGenerator
{
    public function original(string $uuid, string $extension): string
    {
        return sprintf('media/originals/%s/%s/%s.%s', substr($uuid, 0, 2), substr($uuid, 2, 2), $uuid, $extension);
    }

    public function variant(string $uuid, string $name, string $format): string
    {
        return "media/variants/{$uuid}/{$name}.{$format}";
    }
}
