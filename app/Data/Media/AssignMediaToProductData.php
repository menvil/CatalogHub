<?php

namespace App\Data\Media;

final readonly class AssignMediaToProductData
{
    public function __construct(
        public int $mediaAssetId,
        public string $role,
        public ?string $locale,
        public ?int $siteId,
        public ?int $marketId,
    ) {}
}
