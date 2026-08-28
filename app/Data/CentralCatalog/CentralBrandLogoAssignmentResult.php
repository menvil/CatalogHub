<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\Models\MediaAssignment;

final readonly class CentralBrandLogoAssignmentResult
{
    public function __construct(
        public MediaAssignment $assignment,
        public bool $changed,
    ) {}
}
