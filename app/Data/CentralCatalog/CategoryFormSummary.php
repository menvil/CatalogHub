<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

/**
 * Persisted-Category context shown beside the CA-018 Edit form.
 *
 * Definitions match CA-016/017: With Schema means at least one Category
 * attribute assignment (presence, not approval); Products are direct
 * central_products rows; Sites use Site::selectingCategory.
 */
final readonly class CategoryFormSummary
{
    public function __construct(
        public int $sections,
        public int $assignments,
        public int $products,
        public int $selectedSites,
    ) {}

    public function withSchema(): bool
    {
        return $this->assignments > 0;
    }
}
