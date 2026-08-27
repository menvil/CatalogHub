<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\Enums\CentralBrandQualityIssueCode;

final readonly class CentralBrandQualityCheck
{
    /**
     * @param  array<int|string, mixed>  $editorRouteParameters
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public bool $completed,
        public ?CentralBrandQualityIssueCode $issueCode = null,
        public ?string $editorRoute = null,
        public array $editorRouteParameters = [],
        public ?string $editorPermission = null,
        public ?string $editorLabel = null,
        public ?string $locale = null,
    ) {}
}
