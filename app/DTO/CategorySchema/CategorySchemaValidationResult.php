<?php

namespace App\DTO\CategorySchema;

final class CategorySchemaValidationResult
{
    /**
     * @param  list<CategorySchemaIssue>  $issues
     */
    public function __construct(
        private array $issues = [],
    ) {}

    public function add(CategorySchemaIssue $issue): void
    {
        $this->issues[] = $issue;
    }

    /**
     * @return list<CategorySchemaIssue>
     */
    public function issues(): array
    {
        return $this->issues;
    }

    public function hasErrors(): bool
    {
        return collect($this->issues)->contains(fn (CategorySchemaIssue $issue): bool => $issue->severity === 'error');
    }

    public function hasWarnings(): bool
    {
        return collect($this->issues)->contains(fn (CategorySchemaIssue $issue): bool => $issue->severity === 'warning');
    }

    public function hasIssueCode(string $code): bool
    {
        return collect($this->issues)->contains(fn (CategorySchemaIssue $issue): bool => $issue->code === $code);
    }
}
