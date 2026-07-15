<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Data\Pricing\OfferCoverageDashboardData;
use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Services\Pricing\OfferCoverageDashboardBuilder;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use UnexpectedValueException;

final class OffersCoverageDashboard extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.offers-coverage-dashboard';

    protected static ?string $title = 'Offers Coverage Dashboard';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getCoverage(): OfferCoverageDashboardData
    {
        $record = $this->getRecord();

        if (! $record instanceof Site) {
            throw new UnexpectedValueException('Offers coverage dashboard requires a Site record.');
        }

        return app(OfferCoverageDashboardBuilder::class)->build($record);
    }
}
