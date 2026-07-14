<?php

namespace App\Filament\Resources;

use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Filament\Resources\SiteResource\Pages;
use App\Filament\Resources\SiteResource\RelationManagers\SiteFeaturesRelationManager;
use App\Models\Site;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Portal Admin';

    public static function canManageContent(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->hasCatalogHubPermission('site.content.manage'));
    }

    public static function canViewAny(): bool
    {
        return self::canViewSites();
    }

    public static function canView(Model $record): bool
    {
        return self::canViewSites();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canManageSettings(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->hasCatalogHubPermission('site.settings.manage'));
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManageSettings();
    }

    private static function canViewSites(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->hasCatalogHubPermission('sites.manage'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('market_id')->relationship('market', 'name')->required()->disabledOn('edit'), TextInput::make('code')->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('name')->required()->maxLength(255), TextInput::make('domain')->maxLength(255)->unique(ignoreRecord: true),
            Select::make('mode')->required()->options(collect(SiteMode::cases())->mapWithKeys(fn (SiteMode $mode) => [$mode->value => str($mode->value)->headline()]))->disabledOn('edit'),
            TextInput::make('default_locale')->required()->maxLength(255)->disabledOn('edit'), Select::make('status')->required()->options(SiteStatus::options())->default(SiteStatus::default()->value),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('name')->searchable(), TextColumn::make('market.name'), TextColumn::make('mode')->badge(), TextColumn::make('status')->badge()])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSites::route('/'), 'dashboard' => Pages\SiteDashboard::route('/{record}/dashboard'), 'edit' => Pages\EditSite::route('/{record}/edit'), 'products' => Pages\ManageSiteProducts::route('/{record}/products'), 'brands' => Pages\BrandVisibilityRules::route('/{record}/brands'), 'overrides' => Pages\LocalOverrideEditor::route('/{record}/overrides'), 'seo' => Pages\LocalSeoOverride::route('/{record}/seo'), 'themes' => Pages\ThemeSelection::route('/{record}/themes'), 'home-blocks' => Pages\HomepageBlocksEditor::route('/{record}/home-blocks')];
    }

    public static function getRelations(): array
    {
        return [SiteFeaturesRelationManager::class];
    }
}
