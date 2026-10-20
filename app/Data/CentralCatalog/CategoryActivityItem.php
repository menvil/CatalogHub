<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use Carbon\CarbonInterface;

final readonly class CategoryActivityItem
{
    public function __construct(public int $id, public string $label, public string $actor, public CarbonInterface $at, public string $summary) {}
}
