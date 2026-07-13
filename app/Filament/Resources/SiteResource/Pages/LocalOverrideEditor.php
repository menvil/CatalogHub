<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Actions\Sites\UpsertSiteOverrideAction;
use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Models\SiteOverride;
use App\Services\Sites\AllowedSiteOverrideFields;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

final class LocalOverrideEditor extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.local-override-editor';

    protected static ?string $title = 'Local Override Editor';

    public string $entityType = 'product';

    public ?int $entityId = null;

    public string $field = 'local_title';

    public ?string $localeCode = null;

    public string $value = '';

    public ?string $reason = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    /** @return Collection<int, SiteOverride> */
    public function getOverrides(): Collection
    { /** @var Site $site */ $site = $this->getRecord();

        return $site->overrides()->latest()->get();
    }

    public function save(UpsertSiteOverrideAction $action): void
    {
        $data = $this->validate(['entityType' => ['required', 'in:'.implode(',', AllowedSiteOverrideFields::ENTITY_TYPES)], 'entityId' => ['required', 'integer', 'min:1'], 'field' => ['required', 'in:'.implode(',', AllowedSiteOverrideFields::FIELDS)], 'localeCode' => ['nullable', 'string', 'max:32'], 'value' => ['required', 'string'], 'reason' => ['nullable', 'string', 'max:1000']]);
        /** @var Site $site */ $site = $this->getRecord();
        $action->handle($site, $data['entityType'], $data['entityId'], $data['field'], $data['localeCode'], $data['value'], $data['reason']);
        Notification::make()->title('Override saved')->success()->send();
    }
}
