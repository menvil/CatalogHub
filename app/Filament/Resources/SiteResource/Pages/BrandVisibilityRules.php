<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Site;
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

    public function boot(SiteBrandVisibilityService $visibilityService): void
    {
        $this->visibilityService = $visibilityService;
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
        $brands = CentralBrand::query()
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(50);
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
        $brand = CentralBrand::query()->findOrFail($brandId);
        $this->visibilityService->toggle($site, $brand);

        $this->record = $site->fresh();
    }
}
