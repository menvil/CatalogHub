<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Site;
use App\Queries\Sites\SiteBrandVisibilityQuery;
use App\Services\Sites\SiteBrandVisibilityService;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

final class BrandVisibilityRules extends Page
{
    use InteractsWithRecord;
    use WithPagination;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.brand-visibility-rules';

    protected static ?string $title = 'Brand Visibility Rules';

    public string $search = '';

    private SiteBrandVisibilityService $visibilityService;

    private SiteBrandVisibilityQuery $brands;

    public function boot(
        SiteBrandVisibilityService $visibilityService,
        SiteBrandVisibilityQuery $brands,
    ): void {
        $this->visibilityService = $visibilityService;
        $this->brands = $brands;
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

    /**
     * @return array{
     *     brands: LengthAwarePaginator<int, CentralBrand>,
     *     allowedById: array<int, bool>
     * }
     */
    public function getBrandPage(): array
    {
        $brands = $this->brands->paginate($this->search);
        /** @var Site $site */ $site = $this->getRecord();

        return [
            'brands' => $brands,
            'allowedById' => $this->visibilityService->allowsBrands($site, $brands->getCollection()),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggle(int $brandId): void
    {
        /** @var Site $site */ $site = $this->getRecord();
        $brand = $this->brands->findBrand($brandId);
        $this->visibilityService->toggle($site, $brand);

        $this->record = $this->brands->refreshSite($site);
    }
}
