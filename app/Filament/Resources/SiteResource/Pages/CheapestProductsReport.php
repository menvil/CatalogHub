<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Enums\PriceFreshnessStatus;
use App\Filament\Resources\SiteResource;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\MarketMerchant;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Services\Pricing\CheapestProductsQuery;
use App\Services\Pricing\PriceFreshnessCalculator;
use App\Services\Pricing\ValidMarketOfferQuery;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Number;
use UnexpectedValueException;

final class CheapestProductsReport extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.cheapest-products-report';

    protected static ?string $title = 'Cheapest Products';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    /** @return LengthAwarePaginator<int, SiteSearchDocument> */
    public function getProducts(): LengthAwarePaginator
    {
        return app(CheapestProductsQuery::class)->forSite(
            $this->siteRecord(),
            categoryId: $this->filterId(request()->query('category_id')),
            brandId: $this->filterId(request()->query('brand_id')),
            merchantId: $this->filterId(request()->query('merchant_id')),
            freshness: PriceFreshnessStatus::tryFrom((string) request()->query('freshness')),
            inStockOnly: request()->boolean('in_stock'),
        )->paginate(50)->withQueryString();
    }

    /** @return array<int, string> */
    public function getCategoryOptions(): array
    {
        return CentralCategory::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /** @return array<int, string> */
    public function getBrandOptions(): array
    {
        return CentralBrand::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /** @return array<int, string> */
    public function getMerchantOptions(): array
    {
        $ids = app(ValidMarketOfferQuery::class)->forSite($this->siteRecord())
            ->select('market_merchant_id')
            ->distinct();

        return MarketMerchant::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function freshnessLabel(SiteSearchDocument $document): string
    {
        return match (app(PriceFreshnessCalculator::class)->calculate($document)) {
            PriceFreshnessStatus::Fresh => 'Updated recently',
            PriceFreshnessStatus::Stale => 'Price may be outdated',
            PriceFreshnessStatus::Expired => 'Outdated price',
            PriceFreshnessStatus::Unknown => 'Update time unknown',
        };
    }

    public function formattedPrice(SiteSearchDocument $document): string
    {
        return Number::currency(
            (float) $document->min_price,
            in: $this->siteRecord()->market->currency_code,
            locale: $this->siteRecord()->default_locale,
        );
    }

    private function filterId(mixed $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function siteRecord(): Site
    {
        $record = $this->getRecord();

        if (! $record instanceof Site) {
            throw new UnexpectedValueException('Cheapest products report requires a Site record.');
        }

        return $record;
    }
}
