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

    /** @param array<string, mixed> $parameters */
    public static function canAccess(array $parameters = []): bool
    {
        return parent::canAccess($parameters) && SiteResource::canManageContent();
    }

    /** @return Collection<int, CentralBrand> */
    public function getBrands(): Collection
    {
        return CentralBrand::query()->orderBy('name')->get();
    }

    public function toggle(int $brandId): void
    { /** @var Site $site */ $site = $this->getRecord();
        $brand = CentralBrand::query()->findOrFail($brandId);
        app(SiteBrandVisibilityService::class)->toggle($site, $brand);

        $this->record = $site->fresh();
    }

    public function allows(CentralBrand $brand): bool
    {
        /** @var Site $site */ $site = $this->getRecord();

        return app(SiteBrandVisibilityService::class)->allows($site, $brand);
    }
}
