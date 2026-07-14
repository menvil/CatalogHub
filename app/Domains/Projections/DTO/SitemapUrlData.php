<?php

namespace App\Domains\Projections\DTO;

use Carbon\CarbonImmutable;

final readonly class SitemapUrlData
{
    public function __construct(
        public int $siteId,
        public string $locale,
        public string $url,
        public string $entityType,
        public int $entityId,
        public string $changefreq,
        public float $priority,
        public ?CarbonImmutable $lastmodAt,
        public string $status,
        public string $checksum,
    ) {}
}
