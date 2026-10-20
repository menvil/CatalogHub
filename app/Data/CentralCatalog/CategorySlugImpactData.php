<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

/**
 * Read-only consequence assessment of changing one canonical Category slug.
 *
 * The fingerprint covers every fact shown or summarized here (selections, all
 * persisted Category projections, active slug overrides and involved Site
 * states) so Save can reject an impact that changed after it was reviewed.
 */
final readonly class CategorySlugImpactData
{
    /**
     * @param  list<array{name: string, status: string}>  $selectedSites
     * @param  list<CategorySlugImpactRoute>  $routes
     */
    public function __construct(
        public int $categoryId,
        public string $currentSlug,
        public string $proposedSlug,
        public int $selectedSiteCount,
        public array $selectedSites,
        public int $projectionCount,
        public int $currentSlugProjectionCount,
        public int $servedRouteCount,
        public array $routes,
        public int $hiddenRouteCount,
        public string $fingerprint,
    ) {}

    /** Meaningful changes touch real Site selections or persisted public projections. */
    public function requiresAcknowledgement(): bool
    {
        return $this->selectedSiteCount > 0 || $this->projectionCount > 0;
    }
}
