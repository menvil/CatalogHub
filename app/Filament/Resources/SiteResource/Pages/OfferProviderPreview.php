<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Data\Pricing\SiteOfferProviderPreviewData;
use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Services\Pricing\SiteOfferProviderPreviewBuilder;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

final class OfferProviderPreview extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.offer-provider-preview';

    protected static ?string $title = 'Site Price Provider Preview';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getPreview(): SiteOfferProviderPreviewData
    {
        /** @var Site $site */
        $site = $this->getRecord();

        return app(SiteOfferProviderPreviewBuilder::class)->build($site);
    }
}
