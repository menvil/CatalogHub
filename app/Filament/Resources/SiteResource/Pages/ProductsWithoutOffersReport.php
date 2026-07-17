<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Queries\Pricing\ProductsWithoutOffersQuery;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use UnexpectedValueException;

final class ProductsWithoutOffersReport extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.products-without-offers';

    protected static ?string $title = 'Products Without Offers';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    /** @return LengthAwarePaginator<int, SiteProduct> */
    public function getProducts(): LengthAwarePaginator
    {
        return app(ProductsWithoutOffersQuery::class)
            ->paginate(
                $this->siteRecord(),
                $this->filterId(request()->query('category_id')),
                $this->filterId(request()->query('brand_id')),
            )
            ->withQueryString();
    }

    /** @return array<int, string> */
    public function getCategoryOptions(): array
    {
        return CentralCategory::query()
            ->whereIn('id', CentralProduct::query()
                ->select('central_products.central_category_id')
                ->join('site_products', 'site_products.central_product_id', '=', 'central_products.id')
                ->where('site_products.site_id', $this->siteRecord()->id)
                ->where('site_products.visibility', 'visible'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<int, string> */
    public function getBrandOptions(): array
    {
        return CentralBrand::query()
            ->whereIn('id', CentralProduct::query()
                ->select('central_products.central_brand_id')
                ->join('site_products', 'site_products.central_product_id', '=', 'central_products.id')
                ->where('site_products.site_id', $this->siteRecord()->id)
                ->where('site_products.visibility', 'visible'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
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
            throw new UnexpectedValueException('Products without offers report requires a Site record.');
        }

        return $record;
    }
}
