<?php

namespace App\Filament\Pages;

use App\Actions\Sites\CreateSiteAction;
use App\Enums\MarketStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Market;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
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

    /** @var list<string> */
    public const FEATURE_KEYS = ['reviews', 'leads', 'price_comparison', 'comparison', 'polls', 'blog', 'guides', 'external_price_widget', 'local_offers'];

    public function mount(): void
    {
        $this->features = array_fill_keys(self::FEATURE_KEYS, false);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isSiteAdmin());
    }

    /** @return Collection<int, Market> */
    public function getMarkets(): Collection
    {
        return Market::query()->where('status', MarketStatus::Active)->orderBy('name')->get();
    }

    /** @return Collection<int, Locale> */
    public function getLocales(): Collection
    {
        return Locale::query()->active()->orderBy('position')->get();
    }

    /** @return Collection<int, CentralCategory> */
    public function getCategories(): Collection
    {
        return CentralCategory::query()->where('status', 'active')->orderBy('position')->get();
    }

    public function createSite(CreateSiteAction $action): void
    {
        $categoryRules = $this->mode === SiteMode::SingleCategory->value
            ? ['required', 'array', 'size:1']
            : ['required', 'array', 'min:1'];

        $data = $this->validate([
            'code' => ['required', 'string', 'max:255', 'unique:sites,code'], 'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:sites,domain'], 'marketId' => ['required', 'integer', 'exists:markets,id'],
            'mode' => ['required', 'in:single_category,multi_category'], 'enabledLocales' => ['required', 'array', 'min:1'],
            'enabledLocales.*' => ['string', 'exists:locales,code'], 'defaultLocale' => ['required', 'string', 'in:'.implode(',', $this->enabledLocales)],
            'enabledCategories' => $categoryRules, 'enabledCategories.*' => ['integer', 'exists:central_categories,id'], 'features' => ['array'],
        ]);
        $site = $action->handle(['market_id' => $data['marketId'], 'code' => $data['code'], 'name' => $data['name'], 'domain' => $data['domain'], 'mode' => $data['mode'], 'default_locale' => $data['defaultLocale'], 'locales' => $data['enabledLocales'], 'categories' => $data['enabledCategories'], 'features' => $data['features']]);
        $this->createdSiteId = $site->id;
        Notification::make()->title('Site created')->success()->send();
    }
}
