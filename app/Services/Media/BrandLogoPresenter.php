<?php

namespace App\Services\Media;

use App\Models\MediaAsset;
use App\Models\MediaVariant;

final readonly class BrandLogoPresenter
{
    public function __construct(private MediaStorage $storage, private MediaUrlGenerator $urls) {}

    public function forDetail(?MediaAsset $asset): BrandLogoPresentation
    {
        return $this->present($asset, ['brand_logo_256', 'brand_logo_128', 'brand_logo_512']);
    }

    public function forMedia(?MediaAsset $asset): BrandLogoPresentation
    {
        return $this->present($asset, ['brand_logo_512', 'brand_logo_256', 'brand_logo_128']);
    }

    private function present(?MediaAsset $asset, array $preferences): BrandLogoPresentation
    {
        if (! $asset instanceof MediaAsset) {
            return new BrandLogoPresentation(null, null);
        }
        $variants = ($asset->relationLoaded('variants')
            ? $asset->variants->where('status', 'ready')
            : $asset->variants()->where('status', 'ready')->get())
            ->keyBy('variant_type');
        foreach ($preferences as $name) {
            $variant = $variants->get($name);
            if ($variant instanceof MediaVariant && $this->storage->exists($variant->disk, $variant->path)) {
                return new BrandLogoPresentation($asset, $this->urls->forVariant($variant), $name);
            }
        }

        return $this->storage->exists($asset->disk, $asset->original_path) ? new BrandLogoPresentation($asset, $this->urls->forAsset($asset)) : new BrandLogoPresentation(null, null);
    }
}
