<?php

namespace App\Services\Pricing;

use App\Data\Pricing\SiteOfferProviderPreviewData;
use App\Enums\PriceSourceSyncStatus;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Queries\Pricing\ValidMarketOfferQuery;
use Carbon\CarbonInterface;
use Illuminate\Support\Number;

final readonly class SiteOfferProviderPreviewBuilder
{
    public function __construct(
        private ValidMarketOfferQuery $validOffers,
        private SitePriceSourceSelection $sourceSelection,
    ) {}

    public function build(Site $site): SiteOfferProviderPreviewData
    {
        $sources = $this->sourceSelection->enabledSources($site)
            ->orderBy('name')
            ->get();
        $lastSync = PriceSourceSyncLog::query()
            ->whereIn('price_source_id', $sources->modelKeys())
            ->where('status', PriceSourceSyncStatus::Completed)
            ->whereNotNull('finished_at')
            ->latest('finished_at')
            ->first()?->getAttribute('finished_at');
        $sampleOffer = $this->validOffers->forSite($site)
            ->whereIn('central_product_id', SiteProduct::query()
                ->select('central_product_id')
                ->where('site_id', $site->id)
                ->where('visibility', 'visible'))
            ->with('centralProduct')
            ->orderBy('price')
            ->orderBy('id')
            ->first();
        $offers = $sampleOffer instanceof MarketOffer
            ? $this->validOffers->forProduct($site, (int) $sampleOffer->central_product_id)
                ->with('merchant')
                ->orderBy('price')
                ->orderBy('id')
                ->limit(5)
                ->get()
            : collect();
        $mode = data_get($site->settings_json, 'pricing.provider_mode', 'normalized');
        $widgetProvider = data_get($site->settings_json, 'pricing.external_widget.provider');

        return new SiteOfferProviderPreviewData(
            providerMode: is_string($mode) && in_array($mode, ['normalized', 'auto', 'widget'], true)
                ? $mode
                : 'normalized',
            enabledSources: $sources->map(fn (PriceSource $source): array => [
                'name' => (string) $source->getAttribute('name'),
                'status' => $source->status->value,
                'last_sync_at' => $source->last_sync_at?->toIso8601String(),
            ])->values()->all(),
            lastSuccessfulSyncAt: $lastSync instanceof CarbonInterface ? $lastSync : null,
            sampleProductName: $sampleOffer?->centralProduct?->name,
            sampleOffers: $offers->map(fn (MarketOffer $offer): array => [
                'merchant' => (string) $offer->merchant->name,
                'price' => Number::currency(
                    (float) $offer->price,
                    in: (string) $offer->getAttribute('currency'),
                    locale: (string) $site->default_locale,
                ),
                'availability' => str($offer->availability->value)->headline()->toString(),
            ])->values()->all(),
            widgetEnabled: data_get($site->settings_json, 'pricing.external_widget.enabled') === true,
            widgetProvider: is_string($widgetProvider) ? $widgetProvider : null,
        );
    }
}
