<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

final readonly class CentralBrandInput
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public bool $hasWebsiteUrl,
        public ?string $websiteUrl,
        public bool $hasCountryCode,
        public ?string $countryCode,
    ) {}

    /**
     * @return array{
     *     name: string,
     *     slug: string|null,
     *     website_url?: string|null,
     *     country_code?: string|null
     * }
     */
    public function actionPayload(): array
    {
        $payload = [
            'name' => $this->name,
            'slug' => $this->slug,
        ];

        if ($this->hasWebsiteUrl) {
            $payload['website_url'] = $this->websiteUrl;
        }

        if ($this->hasCountryCode) {
            $payload['country_code'] = $this->countryCode;
        }

        return $payload;
    }
}
