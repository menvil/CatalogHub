<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Domains\Themes\Actions\ActivateThemeAction;
use App\Domains\Themes\Services\ThemeFeatureCompatibilityChecker;
use App\Domains\Themes\Services\ThemeRegistry;
use App\Exceptions\Themes\CannotActivateThemeException;
use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Models\Theme;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

final class ThemeSelection extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SiteResource::class;

    protected string $view = 'filament.resources.site-resource.pages.theme-selection';

    protected static ?string $title = 'Theme Selection';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    /** @param array<string, mixed> $parameters */
    public static function canAccess(array $parameters = []): bool
    {
        return parent::canAccess($parameters) && SiteResource::canManageSettings();
    }

    /**
     * @return list<array{
     *     theme: Theme,
     *     compatible: bool,
     *     missingFeatures: list<string>,
     *     warnings: list<string>,
     *     supports: list<string>,
     *     current: bool
     * }>
     */
    public function getThemeOptions(): array
    {
        /** @var Site $site */
        $site = $this->getRecord();
        $registry = app(ThemeRegistry::class);
        $checker = app(ThemeFeatureCompatibilityChecker::class);

        return $registry->activeThemes()
            ->map(function (Theme $theme) use ($site, $registry, $checker): array {
                $compatibility = $checker->check($site, $theme);

                try {
                    $supports = $registry->manifestFor($theme)->supports;
                } catch (\Throwable) {
                    $supports = [];
                }

                return [
                    'theme' => $theme,
                    'compatible' => $compatibility->compatible,
                    'missingFeatures' => $compatibility->missingFeatures,
                    'warnings' => $compatibility->warnings,
                    'supports' => $supports,
                    'current' => (int) $site->getAttribute('theme_id') === $theme->getKey(),
                ];
            })
            ->values()
            ->all();
    }

    public function activate(int $themeId, ActivateThemeAction $action): void
    {
        /** @var Site $site */
        $site = $this->getRecord();
        $theme = Theme::query()->findOrFail($themeId);

        try {
            $action->handle($site, $theme);
        } catch (CannotActivateThemeException $exception) {
            Notification::make()->title('Theme cannot be activated')->body($exception->getMessage())->danger()->send();

            return;
        }

        $this->record = $site->fresh();
        Notification::make()->title('Theme activated')->success()->send();
    }
}
