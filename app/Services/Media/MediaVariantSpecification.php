<?php

namespace App\Services\Media;

final readonly class MediaVariantSpecification
{
    public function __construct(public string $name, public int $width, public int $height, public string $fit, public string $format, public int $quality, public bool $allowUpscale = false) {}

    public function transformHash(): string
    {
        return hash('sha256', json_encode([$this->name, $this->width, $this->height, $this->fit, $this->format, $this->quality, $this->allowUpscale], JSON_THROW_ON_ERROR));
    }
}
