<?php

namespace App\Domains\Projections\DTO;

final readonly class ProductProjectionData
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $seo
     * @param  array<string, mixed>  $media
     */
    public function __construct(
        public int $siteId,
        public string $locale,
        public int $centralProductId,
        public ?string $slug,
        public ?string $title,
        public string $status,
        public array $payload,
        public array $seo,
        public array $media,
        public ?string $checksum,
    ) {}
}
