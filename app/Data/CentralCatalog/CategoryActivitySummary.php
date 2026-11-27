<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

final readonly class CategoryActivitySummary
{
    /** @param list<CategoryActivityItem> $events */
    public function __construct(public array $events, public string $createdBy, public string $lastIdentityUpdateBy) {}
}
