<?php

namespace App\Filament\Pages;

use App\Services\Translations\TranslationStatsService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class TranslationDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|UnitEnum|null $navigationGroup = 'Central Catalog';

    protected static ?string $navigationLabel = 'Translations';

    protected static ?string $title = 'Translation Dashboard';

    protected string $view = 'filament.pages.translation-dashboard';

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return app(TranslationStatsService::class)->dashboard();
    }
}
