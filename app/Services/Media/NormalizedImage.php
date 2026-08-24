<?php

namespace App\Services\Media;

final readonly class NormalizedImage
{
    public function __construct(
        public string $bytes,
        public string $mimeType,
        public string $canonicalExtension,
        public int $width,
        public int $height,
        public int $byteSize,
        public string $checksum,
    ) {}
}
