<?php

namespace App\Services\Media;

use App\Models\MediaAsset;

final readonly class BrandLogoPresentation
{
    public function __construct(public ?MediaAsset $asset, public ?string $url, public ?string $variantName = null) {}
}
