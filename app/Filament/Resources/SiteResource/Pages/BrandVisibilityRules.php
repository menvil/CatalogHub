<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Site;
use App\Services\Sites\SiteBrandVisibilityService;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

final class BrandVisibilityRules extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.brand-visibility-rules';

    protected static ?string $title = 'Brand Visibility Rules';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    /** @return Collection<int, CentralBrand> */
    public function getBrands(): Collection
    {
        return CentralBrand::query()->orderBy('name')->get();
    }

    public function toggle(int $brandId): void
    { /** @var Site $site */ $site = $this->getRecord();
        $brand = CentralBrand::query()->findOrFail($brandId);
        $service = app(SiteBrandVisibilityService::class);
        $service->allows($site, $brand) ? $service->hide($site, $brand) : $service->allow($site, $brand);
        $this->record = $site->fresh();
    }
}
