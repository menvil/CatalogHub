<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Enums\PriceSourceStatus;
use App\Filament\Resources\SiteResource;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\SitePriceSource;
use App\Services\Pricing\SitePriceSourceConfigResolver;
use App\Services\Pricing\SitePriceSourceConfigUpdater;
use App\Services\Pricing\SitePriceSourceSelection;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use UnexpectedValueException;

final class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    /** @var list<int> */
    protected array $enabledPriceSourceIds = [];

    /** @var list<array<string, mixed>> */
    protected array $priceSourceConfigs = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $site = $this->siteRecord();
        $selection = app(SitePriceSourceSelection::class);
        $data['enabled_price_source_ids'] = $selection->hasExplicitSelection($site)
            ? $site->priceSources()->wherePivot('enabled', true)->pluck('price_sources.id')->all()
            : PriceSource::query()
                ->where('market_id', $site->market_id)
                ->where('status', PriceSourceStatus::Active)
                ->pluck('id')
                ->all();
        $configResolver = app(SitePriceSourceConfigResolver::class);
        $data['price_source_configs'] = SitePriceSource::query()
            ->where('site_id', $site->id)
            ->with('priceSource')
            ->get()
            ->map(function (SitePriceSource $pivot) use ($site, $configResolver): array {
                $config = $configResolver->resolve($site, $pivot->priceSource);

                return [
                    'price_source_id' => $pivot->price_source_id,
                    'source_name' => $pivot->priceSource->name,
                    'priority' => $config->priority,
                    'fresh_hours' => $config->freshHours,
                    'stale_hours' => $config->staleHours,
                    'expired_hours' => $config->expiredHours,
                    'allow_default_market_currency' => $config->allowDefaultMarketCurrency,
                    'include_out_of_stock' => $config->includeOutOfStock,
                ];
            })
            ->values()
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $ids = $data['enabled_price_source_ids'] ?? [];
        $this->enabledPriceSourceIds = is_array($ids)
            ? array_values(array_map('intval', $ids))
            : [];
        $configRows = $data['price_source_configs'] ?? [];
        $this->priceSourceConfigs = is_array($configRows)
            ? array_values(array_filter($configRows, 'is_array'))
            : [];
        unset($data['enabled_price_source_ids']);
        unset($data['price_source_configs']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(SitePriceSourceSelection::class)->update($this->siteRecord(), $this->enabledPriceSourceIds);
        app(SitePriceSourceConfigUpdater::class)->update($this->siteRecord(), $this->priceSourceConfigs);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('offerProviderPreview')
                ->label('Price provider preview')
                ->url(fn (): string => OfferProviderPreview::getUrl(['record' => $this->getRecord()])),
        ];
    }

    private function siteRecord(): Site
    {
        $record = $this->getRecord();

        if (! $record instanceof Site) {
            throw new UnexpectedValueException('The Site edit page requires a Site record.');
        }

        return $record;
    }
}
