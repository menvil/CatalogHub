<?php

namespace App\Data\Media;

final readonly class ProductMediaPreviewData
{
    public function __construct(
        public string $role,
        public ?string $locale,
        public ?int $siteId,
        public ?int $marketId,
        public string $mediaSearch,
    ) {}
}
