<?php

namespace App\Domains\Projections\Builders;

use App\Domains\Projections\DTO\ProductProjectionData;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;

final class ProductProjectionBuilder
{
    public function build(Site $site, CentralProduct $product, string $locale): ProductProjectionData
    {
        $product->loadMissing(['brand', 'category']);

        $title = (string) $product->getAttribute('name');
        $slug = (string) $product->getAttribute('slug');
        $status = $product->status === CentralProductStatus::Active ? 'active' : 'pending';
        $payload = [
            'product' => [
                'id' => (int) $product->getKey(),
                'title' => $title,
                'slug' => $slug,
                'model' => $product->getAttribute('model'),
                'status' => $product->status->value,
            ],
            'brand' => $product->brand === null ? null : [
                'id' => (int) $product->brand->getKey(),
                'name' => (string) $product->brand->getAttribute('name'),
                'slug' => (string) $product->brand->getAttribute('slug'),
            ],
            'category' => $product->category === null ? null : [
                'id' => (int) $product->category->getKey(),
                'name' => (string) $product->category->getAttribute('name'),
                'slug' => (string) $product->category->getAttribute('slug'),
            ],
            'site' => [
                'id' => (int) $site->getKey(),
                'code' => (string) $site->getAttribute('code'),
                'locale' => $locale,
            ],
        ];
        $seo = [];
        $media = [];

        return new ProductProjectionData(
            siteId: (int) $site->getKey(),
            locale: $locale,
            centralProductId: (int) $product->getKey(),
            slug: $slug,
            title: $title,
            status: $status,
            payload: $payload,
            seo: $seo,
            media: $media,
            checksum: $this->checksumFor($status, $payload, $seo, $media),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $seo
     * @param  array<string, mixed>  $media
     */
    private function checksumFor(string $status, array $payload, array $seo, array $media): string
    {
        return hash('sha256', json_encode(
            compact('status', 'payload', 'seo', 'media'),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }
}
