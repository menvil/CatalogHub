<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Actions\Sites\UpdateSiteProductVisibilityAction;
use App\Filament\Resources\SiteResource;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class ManageSiteProducts extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.manage-site-products';

    protected static ?string $title = 'Product Visibility';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    /** @return Collection<int, CentralProduct> */
    public function getProducts(): Collection
    {
        /** @var Site $site */ $site = $this->getRecord();
        $categoryIds = DB::table('site_categories')->where('site_id', $site->id)->where('is_enabled', true)->pluck('central_category_id');

        return CentralProduct::query()->whereIn('central_category_id', $categoryIds)->with('brand')->orderBy('name')->get();
    }

    public function setVisibility(int $productId, string $visibility): void
    {
        /** @var Site $site */ $site = $this->getRecord();
        $existing = $site->products()->where('central_product_id', $productId)->first();
        app(UpdateSiteProductVisibilityAction::class)->handle($site, CentralProduct::query()->findOrFail($productId), $visibility, (bool) $existing?->is_featured);
    }

    public function toggleFeatured(int $productId): void
    {
        /** @var Site $site */ $site = $this->getRecord();
        $existing = $site->products()->where('central_product_id', $productId)->first();
        app(UpdateSiteProductVisibilityAction::class)->handle($site, CentralProduct::query()->findOrFail($productId), $existing->visibility ?? 'hidden', ! (bool) $existing?->is_featured);
    }
}
