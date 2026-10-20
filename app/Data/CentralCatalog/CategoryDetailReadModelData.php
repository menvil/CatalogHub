<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\Models\CentralCatalog\CentralCategory;

final readonly class CategoryDetailReadModelData
{
    /**
     * @param  list<array{id: int, name: string}>  $ancestors
     * @param  list<array{name: string, status: string}>  $sites
     */
    public function __construct(
        public CentralCategory $category,
        public array $ancestors,
        public bool $hierarchyAvailable,
        public CategoryDetailSchemaSummary $schema,
        public int $products,
        public int $selectedSites,
        public array $sites,
        public CategoryDetailTranslationSummary $translations,
        public CategoryActivitySummary $activity,
    ) {}
}
