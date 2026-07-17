<?php

namespace App\Filament\Resources;

use App\Enums\PriceSourceStatus;
use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Filament\Resources\SiteResource\Pages;
use App\Filament\Resources\SiteResource\RelationManagers\SiteFeaturesRelationManager;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
        return $record instanceof Site
            && self::canViewSites()
            && self::canAccessSite($record);
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
        return $record instanceof Site
            && self::canManageSettings()
            && self::canAccessSite($record);
    }

    /** @return Builder<Site> */
    public static function getEloquentQuery(): Builder
    {
        $query = Site::query();
        $user = auth()->user();

        if (
            $user instanceof User
            && $user->site_id !== null
            && ! $user->isSuperAdmin()
            && ! $user->isCentralAdmin()
        ) {
            return $query->whereKey($user->site_id);
        }

        return $query;
    }

    public static function canAccessSite(Site $site): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin()
                || $user->isCentralAdmin()
                || ($user->site_id !== null && (int) $user->site_id === (int) $site->getKey()));
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
            Select::make('enabled_price_source_ids')
                ->label('Enabled price sources')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(fn (?Model $record): array => ! $record instanceof Site ? [] : PriceSource::query()
                    ->where('market_id', $record->market_id)
                    ->where('status', PriceSourceStatus::Active)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->helperText('Only sources from this site market can be selected.'),
            Repeater::make('price_source_configs')
                ->label('Market-specific price source config')
                ->schema([
                    Hidden::make('price_source_id'),
                    TextInput::make('source_name')->label('Source')->disabled()->dehydrated(false),
                    TextInput::make('priority')->integer()->minValue(0)->maxValue(65535),
                    TextInput::make('fresh_hours')->label('Fresh hours')->integer()->minValue(0)->required(),
                    TextInput::make('stale_hours')->label('Stale hours')->integer()->minValue(0)->required(),
                    TextInput::make('expired_hours')->label('Expired hours')->integer()->minValue(0)->required(),
                    Toggle::make('allow_default_market_currency')->default(true),
                    Toggle::make('include_out_of_stock')->default(true),
                ])
                ->columns(3)
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columnSpanFull()
                ->helperText('Save source selection once before configuring newly enabled sources.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('name')->searchable(), TextColumn::make('market.name'), TextColumn::make('mode')->badge(), TextColumn::make('status')->badge()])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'dashboard' => Pages\SiteDashboard::route('/{record}/dashboard'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
            'products' => Pages\ManageSiteProducts::route('/{record}/products'),
            'brands' => Pages\BrandVisibilityRules::route('/{record}/brands'),
            'overrides' => Pages\LocalOverrideEditor::route('/{record}/overrides'),
            'seo' => Pages\LocalSeoOverride::route('/{record}/seo'),
            'themes' => Pages\ThemeSelection::route('/{record}/themes'),
            'home-blocks' => Pages\HomepageBlocksEditor::route('/{record}/home-blocks'),
            'pricing-preview' => Pages\OfferProviderPreview::route('/{record}/pricing/preview'),
            'products-without-offers' => Pages\ProductsWithoutOffersReport::route('/{record}/pricing/products-without-offers'),
            'offers-coverage' => Pages\OffersCoverageDashboard::route('/{record}/pricing/coverage'),
            'cheapest-products' => Pages\CheapestProductsReport::route('/{record}/pricing/cheapest-products'),
        ];
    }

    public static function getRelations(): array
    {
        return [SiteFeaturesRelationManager::class];
    }
}
