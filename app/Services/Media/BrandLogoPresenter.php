<?php

namespace App\Services\Media;

use App\Enums\MediaDeliveryState;
use App\Models\MediaAsset;
use App\Models\MediaVariant;
use Throwable;

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

    /** @return list<BrandLogoVariantPresentation> */
    public function variantsForMedia(?MediaAsset $asset): array
    {
        if (! $asset instanceof MediaAsset) {
            return [];
        }

        $variants = $asset->relationLoaded('variants')
            ? $asset->variants
            : $asset->variants()
                ->whereIn('variant_type', ['brand_logo_128', 'brand_logo_256', 'brand_logo_512'])
                ->whereNull('locale')
                ->whereNull('site_id')
                ->whereNull('market_id')
                ->get();

        $byName = $variants->keyBy('variant_type');
        $presentations = [];
        foreach (['brand_logo_128', 'brand_logo_256', 'brand_logo_512'] as $name) {
            $variant = $byName->get($name);
            if (! $variant instanceof MediaVariant) {
                continue;
            }

            $state = $this->variantState($variant);
            $presentations[] = new BrandLogoVariantPresentation(
                name: $name,
                state: $state,
                width: $variant->width,
                height: $variant->height,
                fileSize: $variant->file_size,
                format: $variant->format,
                url: $state === MediaDeliveryState::Ready ? $this->urls->forVariant($variant) : null,
            );
        }

        return $presentations;
    }

    private function present(?MediaAsset $asset, array $preferences): BrandLogoPresentation
    {
        if (! $asset instanceof MediaAsset) {
            return new BrandLogoPresentation(null, null, MediaDeliveryState::Missing);
        }

        $assetState = match ((string) $asset->status) {
            'active' => null,
            'pending', 'processing' => MediaDeliveryState::Processing,
            'failed' => MediaDeliveryState::Failed,
            default => MediaDeliveryState::Unavailable,
        };
        if ($assetState instanceof MediaDeliveryState) {
            return new BrandLogoPresentation($asset, null, $assetState);
        }

        $variants = ($asset->relationLoaded('variants')
            ? $asset->variants->where('status', 'ready')
            : $asset->variants()
                ->whereIn('variant_type', $preferences)
                ->whereNull('locale')
                ->whereNull('site_id')
                ->whereNull('market_id')
                ->where('status', 'ready')
                ->get())
            ->keyBy('variant_type');
        foreach ($preferences as $name) {
            $variant = $variants->get($name);
            if ($variant instanceof MediaVariant && $this->exists($variant->disk, $variant->path)) {
                return new BrandLogoPresentation($asset, $this->urls->forVariant($variant), MediaDeliveryState::Ready, $name);
            }
        }

        return $this->exists($asset->disk, $asset->original_path)
            ? new BrandLogoPresentation($asset, $this->urls->forAsset($asset), MediaDeliveryState::Ready)
            : new BrandLogoPresentation($asset, null, MediaDeliveryState::Unavailable);
    }

    private function variantState(MediaVariant $variant): MediaDeliveryState
    {
        return match ((string) $variant->status) {
            'pending', 'processing' => MediaDeliveryState::Processing,
            'failed' => MediaDeliveryState::Failed,
            'ready' => $this->exists($variant->disk, $variant->path)
                ? MediaDeliveryState::Ready
                : MediaDeliveryState::Unavailable,
            default => MediaDeliveryState::Unavailable,
        };
    }

    private function exists(string $disk, string $path): bool
    {
        try {
            return $this->storage->exists($disk, $path);
        } catch (Throwable) {
            return false;
        }
    }
}
