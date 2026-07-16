<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Actions\Sites\UpdateSiteProductVisibilityAction;
use App\Enums\CentralProductStatus;
use App\Filament\Resources\SiteResource;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
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
        $categoryIds = $site->categories()->enabled()->pluck('central_category_id');

        return CentralProduct::query()
            ->whereIn('central_category_id', $categoryIds)
            ->where('status', CentralProductStatus::Active)
            ->when($this->search !== '', fn ($query) => $query->whereLike('name', '%'.$this->search.'%'))
            ->with('brand')
            ->orderBy('name')
            ->paginate(50);
    }

    /** @param list<int> $productIds
     * @return Collection<int, SiteProduct>
     */
    public function getSiteProductStates(array $productIds): Collection
    {
        /** @var Site $site */ $site = $this->getRecord();

        return $site->products()->whereIn('central_product_id', $productIds)->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setVisibility(int $productId, string $visibility): void
    {
        /** @var Site $site */ $site = $this->getRecord();
        app(UpdateSiteProductVisibilityAction::class)->handle($site, CentralProduct::query()->findOrFail($productId), $visibility);
    }

    public function toggleFeatured(int $productId): void
    {
        /** @var Site $site */ $site = $this->getRecord();
        app(UpdateSiteProductVisibilityAction::class)->toggleFeatured($site, CentralProduct::query()->findOrFail($productId));
    }
}
