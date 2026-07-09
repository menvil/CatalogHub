<?php

namespace App\DTO\CategorySchema;

use App\Enums\CategorySchemaIssueSeverity;

final readonly class CategorySchemaIssue
{
    public function __construct(
        public CategorySchemaIssueSeverity $severity,
        public string $code,
        public string $message,
        public ?string $entityType = null,
        public ?int $entityId = null,
    ) {}
}
