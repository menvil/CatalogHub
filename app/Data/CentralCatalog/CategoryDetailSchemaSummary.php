<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\DTO\CategorySchema\CategorySchemaIssue;

final readonly class CategoryDetailSchemaSummary
{
    /** @param list<CategorySchemaIssue> $issues */
    public function __construct(
        public int $sections,
        public int $attributes,
        public int $required,
        public int $facets,
        public int $comparison,
        public ?string $reviewer,
        public ?string $approver,
        public int $issueCount,
        public array $issues,
    ) {}
}
