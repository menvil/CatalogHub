<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

final readonly class CentralBrandInput
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public bool $hasWebsiteUrl = false,
        public ?string $websiteUrl = null,
        public bool $hasCountryCode = false,
        public ?string $countryCode = null,
    ) {}
}
