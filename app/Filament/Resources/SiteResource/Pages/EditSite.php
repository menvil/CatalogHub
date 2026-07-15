<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Enums\PriceSourceStatus;
use App\Filament\Resources\SiteResource;
use App\Models\PriceSource;
use App\Models\Site;
use App\Services\Pricing\SitePriceSourceSelection;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use UnexpectedValueException;

final class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    /** @var list<int> */
    protected array $enabledPriceSourceIds = [];

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

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $ids = $data['enabled_price_source_ids'] ?? [];
        $this->enabledPriceSourceIds = is_array($ids)
            ? array_values(array_map('intval', $ids))
            : [];
        unset($data['enabled_price_source_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(SitePriceSourceSelection::class)->update($this->siteRecord(), $this->enabledPriceSourceIds);
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
