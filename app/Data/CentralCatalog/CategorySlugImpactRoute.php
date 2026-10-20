<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

/**
 * One persisted Site/Locale Category projection and what a slug change means for it.
 *
 * Paths come from the existing public route; absolute URLs are present only when
 * the projection is served now and the Site host used by LocalizedUrlResolver is
 * one of the Site's active public domains.
 */
final readonly class CategorySlugImpactRoute
{
    public function __construct(
        public string $siteName,
        public string $siteStatus,
        public string $locale,
        public string $projectionStatus,
        public bool $servedNow,
        public ?string $notServedReason,
        public string $currentSlug,
        public string $currentPath,
        public ?string $currentUrl,
        public string $slugAfterRebuild,
        public string $pathAfterRebuild,
        public ?string $urlAfterRebuild,
        public bool $keptByOverride,
    ) {}

    public function changesOnRebuild(): bool
    {
        return $this->slugAfterRebuild !== $this->currentSlug;
    }
}
