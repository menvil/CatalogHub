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
use Illuminate\Support\Facades\DB;

final class LocalSeoOverride extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.local-seo-override';

    protected static ?string $title = 'Local SEO Override';

    public string $entityType = 'product';

    public ?int $entityId = null;

    public ?string $localeCode = null;

    public string $metaTitle = '';

    public string $metaDescription = '';

    public string $introText = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function updatedEntityType(): void
    {
        $this->loadExistingValues();
    }

    public function updatedEntityId(): void
    {
        $this->loadExistingValues();
    }

    public function updatedLocaleCode(): void
    {
        $this->loadExistingValues();
    }

    public function loadExistingValues(): void
    {
        $values = [
            'meta_title' => '',
            'meta_description' => '',
            'intro_text' => '',
        ];

        if ($this->entityId !== null && filled($this->localeCode)) {
            /** @var Site $site */ $site = $this->getRecord();
            $overrides = $site->overrides()
                ->where('entity_type', $this->entityType)
                ->where('entity_id', $this->entityId)
                ->where('locale_code', $this->localeCode)
                ->whereIn('field', array_keys($values))
                ->where('status', 'active')
                ->get();

            foreach ($overrides as $override) {
                /** @var SiteOverride $override */
                $field = (string) $override->field;

                if (array_key_exists($field, $values)) {
                    $values[$field] = (string) $override->value();
                }
            }
        }

        $this->metaTitle = $values['meta_title'];
        $this->metaDescription = $values['meta_description'];
        $this->introText = $values['intro_text'];
    }

    public function save(UpsertSiteOverrideAction $action): void
    {
        $data = $this->validate(['entityType' => ['required', 'in:'.implode(',', AllowedSiteOverrideFields::ENTITY_TYPES)], 'entityId' => ['required', 'integer', 'min:1'], 'localeCode' => ['required', 'string', 'max:32'], 'metaTitle' => ['nullable', 'string', 'max:255'], 'metaDescription' => ['nullable', 'string', 'max:1000'], 'introText' => ['nullable', 'string', 'max:5000']]);
        /** @var Site $site */ $site = $this->getRecord();
        DB::transaction(function () use ($action, $data, $site): void {
            $action->handle($site, $data['entityType'], $data['entityId'], 'meta_title', $data['localeCode'], $data['metaTitle']);
            $action->handle($site, $data['entityType'], $data['entityId'], 'meta_description', $data['localeCode'], $data['metaDescription']);
            $action->handle($site, $data['entityType'], $data['entityId'], 'intro_text', $data['localeCode'], $data['introText']);
        });
        Notification::make()->title('Local SEO saved')->success()->send();
    }
}
