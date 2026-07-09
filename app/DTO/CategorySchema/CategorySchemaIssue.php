<?php

namespace App\DTO\CategorySchema;

final readonly class CategorySchemaIssue
{
    public function __construct(
        public string $severity,
        public string $code,
        public string $message,
        public ?string $entityType = null,
        public ?int $entityId = null,
    ) {}
}
