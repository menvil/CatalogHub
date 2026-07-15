<?php

namespace App\Data\Pricing;

use Carbon\CarbonInterface;

final readonly class SiteOfferProviderPreviewData
{
    /**
     * @param  list<array{name: string, status: string, last_sync_at: ?string}>  $enabledSources
     * @param  list<array{merchant: string, price: string, availability: string}>  $sampleOffers
     */
    public function __construct(
        public string $providerMode,
        public array $enabledSources,
        public ?CarbonInterface $lastSuccessfulSyncAt,
        public ?string $sampleProductName,
        public array $sampleOffers,
        public bool $widgetEnabled,
        public ?string $widgetProvider,
    ) {}
}
