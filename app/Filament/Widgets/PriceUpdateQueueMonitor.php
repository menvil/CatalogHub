<?php

namespace App\Filament\Widgets;

use App\Data\Pricing\PriceUpdateQueueStatusData;
use App\Filament\Resources\SiteResource\Pages\OfferProviderPreview;
use App\Models\Site;
use App\Services\Pricing\PriceUpdateQueueStatusBuilder;
use Filament\Widgets\Widget;

final class PriceUpdateQueueMonitor extends Widget
{
    protected string $view = 'filament.widgets.price-update-queue-monitor';

    public ?int $siteId = null;

    public function getStatus(): PriceUpdateQueueStatusData
    {
        $site = Site::query()->find($this->siteId);

        if (! $site instanceof Site) {
            return new PriceUpdateQueueStatusData(0, 0, 0, null, null, null, null);
        }

        return app(PriceUpdateQueueStatusBuilder::class)->build($site);
    }

    public function getSyncStatusUrl(): string
    {
        if ($this->siteId === null) {
            return '#';
        }

        return OfferProviderPreview::getUrl(['record' => $this->siteId]).'#source-sync-status';
    }
}
