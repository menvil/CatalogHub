<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Enums\MediaDeliveryState;

final readonly class BrandLogoVariantPresentation
{
    public function __construct(
        public string $name,
        public MediaDeliveryState $state,
        public ?int $width,
        public ?int $height,
        public ?int $fileSize,
        public ?string $format,
        public ?string $url,
    ) {}
}
