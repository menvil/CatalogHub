<?php

namespace App\Domains\Projections\Builders;

use App\Domains\Projections\DTO\CategoryProjectionData;
use App\Domains\Projections\DTO\ProductProjectionData;
use App\Domains\Projections\DTO\SitemapUrlData;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\Site;
use Carbon\CarbonImmutable;

final class SitemapBuilder
{
    public function fromProductProjection(
        Site $site,
        ProductProjectionData $projection,
    ): SitemapUrlData {
        return $this->make(
            site: $site,
            siteId: $projection->siteId,
            locale: $projection->locale,
            entityType: 'product',
            entityId: $projection->centralProductId,
            slug: $projection->slug ?? (string) $projection->centralProductId,
            canonicalUrl: $projection->seo['canonical_url'] ?? null,
            changefreq: 'weekly',
            priority: 0.8,
            lastmodAt: $projection->builtAt,
            active: $projection->status === ProjectionStatus::Active,
        );
    }

    public function fromCategoryProjection(
        Site $site,
        CategoryProjectionData $projection,
    ): SitemapUrlData {
        return $this->make(
            site: $site,
            siteId: $projection->siteId,
            locale: $projection->locale,
            entityType: 'category',
            entityId: $projection->centralCategoryId,
            slug: $projection->slug,
            canonicalUrl: $projection->seo['canonical_url'] ?? null,
            changefreq: 'daily',
            priority: 0.7,
            lastmodAt: $projection->builtAt,
            active: $projection->status === ProjectionStatus::Active,
        );
    }

    private function make(
        Site $site,
        int $siteId,
        string $locale,
        string $entityType,
        int $entityId,
        string $slug,
        mixed $canonicalUrl,
        string $changefreq,
        float $priority,
        ?CarbonImmutable $lastmodAt,
        bool $active,
    ): SitemapUrlData {
        $url = is_string($canonicalUrl) && $canonicalUrl !== ''
            ? $canonicalUrl
            : $this->baseUrl($site).'/'.($entityType === 'product' ? 'products' : 'categories').'/'.rawurlencode($slug);
        $status = $active ? 'active' : 'inactive';
        $checksum = hash('sha256', json_encode([
            'site_id' => $siteId,
            'locale' => $locale,
            'url' => $url,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'changefreq' => $changefreq,
            'priority' => $priority,
            'lastmod_at' => $lastmodAt?->toAtomString(),
            'status' => $status,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return new SitemapUrlData(
            siteId: $siteId,
            locale: $locale,
            url: $url,
            entityType: $entityType,
            entityId: $entityId,
            changefreq: $changefreq,
            priority: $priority,
            lastmodAt: $lastmodAt,
            status: $status,
            checksum: $checksum,
        );
    }

    private function baseUrl(Site $site): string
    {
        $domain = trim((string) $site->getAttribute('domain'));
        $baseUrl = $domain !== '' ? $domain : (string) config('app.url');

        if (! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://'.$baseUrl;
        }

        return rtrim($baseUrl, '/');
    }
}
