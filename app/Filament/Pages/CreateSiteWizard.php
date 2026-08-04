<?php

namespace App\Filament\Pages;

use App\Actions\Sites\CreateSiteAction;
use App\Enums\CentralCategoryStatus;
use App\Enums\MarketStatus;
use App\Enums\SiteMode;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Market;
use App\Models\SiteFeature;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

final class CreateSiteWizard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Portal Admin';

    protected static ?string $navigationLabel = 'Create site';

    protected static ?string $title = 'Create Site Wizard';

    protected string $view = 'filament.pages.create-site-wizard';

    public string $code = '';

    public string $name = '';

    public ?string $domain = null;

    public ?int $marketId = null;

    public string $mode = 'multi_category';

    /** @var list<string> */
    public array $enabledLocales = [];

    public string $defaultLocale = '';

    /** @var list<int> */
    public array $enabledCategories = [];

    /** @var array<string, bool> */
    public array $features = [];

    public ?int $createdSiteId = null;

    public int $currentStep = 0;

    public function mount(): void
    {
        $this->features = array_fill_keys(SiteFeature::KEYS, false);
    }

    public function previousStep(): void
    {
        $this->currentStep = max(0, $this->currentStep - 1);
    }

    public function nextStep(): void
    {
        if ($this->currentStep >= 6) {
            return;
        }

        $this->validate($this->rulesForStep($this->currentStep));
        $this->currentStep = min(6, $this->currentStep + 1);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && ($user->can('central.manage') || $user->can('sites.manage'));
    }

    /** @return Collection<int, Market> */
    public function getMarkets(): Collection
    {
        return Market::query()->where('status', MarketStatus::Active)->orderBy('name')->get();
    }

    public function getSelectedMarket(): ?Market
    {
        return $this->marketId === null ? null : Market::query()->find($this->marketId);
    }

    public function updatedMarketId(): void
    {
        $market = $this->getSelectedMarket();

        if ($market instanceof Market && Locale::query()->where('code', $market->default_locale)->where('is_active', true)->exists()) {
            $this->defaultLocale = $market->default_locale;
            $this->enabledLocales = array_values(array_unique([...$this->enabledLocales, $market->default_locale]));
        }
    }

    /** @return Collection<int, Locale> */
    public function getLocales(): Collection
    {
        return Locale::query()->active()->orderBy('position')->get();
    }

    /** @return Collection<int, CentralCategory> */
    public function getCategories(): Collection
    {
        return CentralCategory::query()->where('status', CentralCategoryStatus::Active)->orderBy('position')->get();
    }

    public function createSite(CreateSiteAction $action): void
    {
        try {
            $data = $this->validate($this->allRules());
        } catch (ValidationException $exception) {
            $this->showStepForErrors($exception->errors());

            throw $exception;
        }

        try {
            $site = $action->handle(['market_id' => $data['marketId'], 'code' => $data['code'], 'name' => $data['name'], 'domain' => $data['domain'], 'mode' => $data['mode'], 'default_locale' => $data['defaultLocale'], 'locales' => $data['enabledLocales'], 'categories' => $data['enabledCategories'], 'features' => $data['features']]);
        } catch (ValidationException $exception) {
            $fieldMap = [
                'market_id' => 'marketId',
                'locales' => 'enabledLocales',
                'default_locale' => 'defaultLocale',
                'categories' => 'enabledCategories',
            ];
            $errors = [];

            foreach ($exception->errors() as $field => $messages) {
                $errors[$fieldMap[$field] ?? $field] = $messages;
            }

            $this->showStepForErrors($errors);

            throw ValidationException::withMessages($errors);
        }

        $this->createdSiteId = $site->id;
        Notification::make()->title('Site created')->success()->send();
    }

    /** @return array<string, list<string>> */
    private function rulesForStep(int $step): array
    {
        return match ($step) {
            0 => [
                'code' => ['required', 'string', 'max:255', 'unique:sites,code'],
                'name' => ['required', 'string', 'max:255'],
                'domain' => ['nullable', 'string', 'max:255', 'unique:sites,domain'],
            ],
            1 => [
                'marketId' => ['required', 'integer', 'exists:markets,id'],
            ],
            2 => [
                'mode' => ['required', 'in:single_category,multi_category'],
            ],
            3 => [
                'enabledLocales' => ['required', 'array', 'min:1'],
                'enabledLocales.*' => ['string', 'exists:locales,code'],
                'defaultLocale' => ['required', 'string', 'in:'.implode(',', $this->enabledLocales)],
            ],
            4 => [
                'enabledCategories' => $this->mode === SiteMode::SingleCategory->value
                    ? ['required', 'array', 'size:1']
                    : ['required', 'array', 'min:1'],
                'enabledCategories.*' => ['integer', 'exists:central_categories,id'],
            ],
            5 => [
                'features' => ['array'],
                'features.*' => ['boolean:strict'],
            ],
            default => [],
        };
    }

    /** @return array<string, list<string>> */
    private function allRules(): array
    {
        return array_merge(...array_map(fn (int $step): array => $this->rulesForStep($step), range(0, 5)));
    }

    /** @param array<string, list<string>> $errors */
    private function showStepForErrors(array $errors): void
    {
        $field = array_key_first($errors);

        if ($field === null) {
            return;
        }

        $rootField = explode('.', $field)[0];
        $this->currentStep = match ($rootField) {
            'code', 'name', 'domain' => 0,
            'marketId' => 1,
            'mode' => 2,
            'enabledLocales', 'defaultLocale' => 3,
            'enabledCategories' => 4,
            'features' => 5,
            default => $this->currentStep,
        };
    }
}
