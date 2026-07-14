<?php

namespace App\Domains\Projections\DTO;

use App\Domains\Projections\Enums\ProjectionStatus;
use Carbon\CarbonImmutable;

final readonly class CategoryProjectionData
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $seo
     * @param  list<array<string, mixed>>  $facets
     * @param  list<array<string, mixed>>  $comparison
     */
    public function __construct(
        public int $siteId,
        public string $locale,
        public int $centralCategoryId,
        public ?int $parentCategoryId,
        public string $slug,
        public string $title,
        public ProjectionStatus $status,
        public array $payload,
        public array $seo,
        public array $facets,
        public array $comparison,
        public string $checksum,
        public ?CarbonImmutable $builtAt = null,
    ) {}
}
