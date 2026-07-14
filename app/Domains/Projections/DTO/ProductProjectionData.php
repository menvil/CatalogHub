<?php

namespace App\Domains\Projections\DTO;

use App\Domains\Projections\Enums\ProjectionStatus;
use Carbon\CarbonImmutable;

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
        public ProjectionStatus $status,
        public array $payload,
        public array $seo,
        public array $media,
        public ?string $checksum,
        public ?CarbonImmutable $builtAt = null,
    ) {}
}
