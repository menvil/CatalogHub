<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Actions\Sites\UpdateSiteProductVisibilityAction;
use App\Filament\Resources\SiteResource;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Queries\Sites\SiteProductManagementQuery;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Livewire\WithPagination;

final class ManageSiteProducts extends Page
{
    use InteractsWithRecord;
    use WithPagination;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.manage-site-products';

    protected static ?string $title = 'Product Visibility';

    public string $search = '';

    private SiteProductManagementQuery $products;

    public function boot(SiteProductManagementQuery $products): void
    {
        $this->products = $products;
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    /** @param array<string, mixed> $parameters */
    public static function canAccess(array $parameters = []): bool
    {
        return parent::canAccess($parameters) && SiteResource::canManageContent();
    }

    /** @return LengthAwarePaginator<int, CentralProduct> */
    public function getProducts(): LengthAwarePaginator
    {
        /** @var Site $site */ $site = $this->getRecord();

        return $this->products->paginate($site, $this->search);
    }

    /** @param list<int> $productIds
     * @return Collection<int, SiteProduct>
     */
    public function getSiteProductStates(array $productIds): Collection
    {
        /** @var Site $site */ $site = $this->getRecord();

        return $this->products->states($site, $productIds);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setVisibility(int $productId, string $visibility): void
    {
        /** @var Site $site */ $site = $this->getRecord();
        app(UpdateSiteProductVisibilityAction::class)->handle(
            $site,
            $this->products->findProduct($productId),
            $visibility,
        );
    }

    public function toggleFeatured(int $productId): void
    {
        /** @var Site $site */ $site = $this->getRecord();
        app(UpdateSiteProductVisibilityAction::class)->toggleFeatured(
            $site,
            $this->products->findProduct($productId),
        );
    }
}
