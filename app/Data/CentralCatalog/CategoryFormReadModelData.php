<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\Models\CentralCatalog\CentralCategory;

/**
 * Prepared CA-018 Create/Edit view data. Create has no Category, siblings or summary.
 *
 * Scope revisions are expected-state tokens keyed by parent option value
 * ('' for Root), read without initializing missing hierarchy scope rows.
 */
final readonly class CategoryFormReadModelData
{
    /**
     * @param  list<array{value: string, label: string, search: string}>  $parentOptions
     * @param  array<string, int>  $scopeRevisions
     * @param  list<array{id: int, name: string}>  $ancestors
     * @param  list<array{id: int, name: string}>  $siblings
     */
    public function __construct(
        public ?CentralCategory $category,
        public array $parentOptions,
        public array $scopeRevisions,
        public ?int $parentId,
        public array $ancestors,
        public bool $hierarchyAvailable,
        public array $siblings,
        public bool $siblingOrderContiguous,
        public ?CategoryFormSummary $summary,
        public ?int $translationLocaleId,
    ) {}

    public function scopeRevisionFor(?int $parentId): int
    {
        return $this->scopeRevisions[$parentId === null ? '' : (string) $parentId] ?? 0;
    }

    public function position(): ?int
    {
        foreach ($this->siblings as $index => $sibling) {
            if ($this->category !== null && $sibling['id'] === $this->category->id) {
                return $index;
            }
        }

        return null;
    }
}
