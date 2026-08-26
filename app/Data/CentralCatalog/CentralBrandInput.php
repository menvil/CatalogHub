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
        public bool $hasCountryId = false,
        public ?int $countryId = null,
        public bool $hasFoundedYear = false,
        public ?int $foundedYear = null,
        public bool $hasSupportUrl = false,
        public ?string $supportUrl = null,
        public bool $hasContactEmail = false,
        public ?string $contactEmail = null,
        public bool $hasPrimaryColor = false,
        public ?string $primaryColor = null,
    ) {}
}
