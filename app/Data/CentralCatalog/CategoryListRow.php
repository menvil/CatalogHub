<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\Enums\CategorySchemaStatus;
use App\Enums\CentralCategoryStatus;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralCategory;

final readonly class CategoryListRow
{
    public function __construct(
        public CentralCategory $category,
        public ?int $depth,
        public ?string $parentName,
        public int $products,
        public int $attributes,
        public int $facets,
        public int $sites,
        public int $localeTotal,
        public int $covered,
        public int $missing,
        public int $outdated,
        public ?TranslationStatus $localeStatus,
    ) {}

    public function localeStatusLabel(): ?string
    {
        return $this->localeStatus === null ? null : TranslationStatus::options()[$this->localeStatus->value];
    }

    public function coveragePercentage(): ?int
    {
        return $this->localeTotal === 0 ? null : (int) round($this->covered / $this->localeTotal * 100);
    }

    public function hierarchyLabel(): string
    {
        return $this->depth === null ? 'Hierarchy unavailable' : ($this->depth === 0 ? 'Root' : 'Level '.$this->depth.' · Parent: '.$this->parentName);
    }

    public function statusTone(): string
    {
        return match ($this->category->status) {
            CentralCategoryStatus::Active => 'success',
            CentralCategoryStatus::Archived => 'neutral',
            CentralCategoryStatus::Draft => 'info',
        };
    }

    public function schemaTone(): string
    {
        return match ($this->category->schema_status) {
            CategorySchemaStatus::Draft => 'warning',
            CategorySchemaStatus::Reviewed => 'info',
            CategorySchemaStatus::Approved => 'success',
            CategorySchemaStatus::Archived => 'neutral',
        };
    }
}
