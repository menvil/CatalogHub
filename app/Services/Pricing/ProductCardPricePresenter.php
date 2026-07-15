<?php

namespace App\Services\Pricing;

use App\Enums\PriceFreshnessStatus;
use App\Models\SiteSearchDocument;
use Carbon\CarbonInterface;
use Illuminate\Support\Number;

final readonly class ProductCardPricePresenter
{
    public function __construct(private PriceFreshnessCalculator $freshness) {}

    /**
     * @return array{
     *     has_offers: bool,
     *     formatted_min_price: string|null,
     *     offers_count: int,
     *     in_stock: bool,
     *     freshness: string,
     *     freshness_summary: string
     * }
     */
    public function present(SiteSearchDocument $document, string $currency, string $locale): array
    {
        $offersCount = (int) $document->getAttribute('offers_count');
        $minimumPrice = $document->getAttribute('min_price');
        $hasOffers = $offersCount > 0 && is_numeric($minimumPrice);
        $freshness = $this->freshness->calculate($document);

        return [
            'has_offers' => $hasOffers,
            'formatted_min_price' => $hasOffers
                ? $this->formatPrice((float) $minimumPrice, $currency, $locale)
                : null,
            'offers_count' => $offersCount,
            'in_stock' => $hasOffers && (bool) $document->getAttribute('in_stock'),
            'freshness' => $freshness->value,
            'freshness_summary' => $this->freshnessSummary($document, $freshness),
        ];
    }

    private function formatPrice(float $price, string $currency, string $locale): string
    {
        $formatted = Number::currency($price, in: $currency, locale: $locale, precision: 2);

        return is_string($formatted) ? $formatted : $currency.' '.number_format($price, 2);
    }

    private function freshnessSummary(
        SiteSearchDocument $document,
        PriceFreshnessStatus $freshness,
    ): string {
        return match ($freshness) {
            PriceFreshnessStatus::Fresh => $this->freshSummary($document),
            PriceFreshnessStatus::Stale => 'Price may be outdated',
            PriceFreshnessStatus::Expired => 'Outdated price',
            PriceFreshnessStatus::Unknown => 'Update time unknown',
        };
    }

    private function freshSummary(SiteSearchDocument $document): string
    {
        $updatedAt = $document->getAttribute('last_price_update_at');

        return $updatedAt instanceof CarbonInterface
            ? 'Updated '.$updatedAt->diffForHumans()
            : 'Updated recently';
    }
}
