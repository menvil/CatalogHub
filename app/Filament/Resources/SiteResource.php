<?php

namespace App\Filament\Resources;

use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Filament\Resources\SiteResource\Pages;
use App\Filament\Resources\SiteResource\RelationManagers\SiteFeaturesRelationManager;
use App\Models\Site;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Portal Admin';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('market_id')->relationship('market', 'name')->required(), TextInput::make('code')->required()->unique(ignoreRecord: true),
            TextInput::make('name')->required(), TextInput::make('domain')->unique(ignoreRecord: true),
            Select::make('mode')->required()->options(collect(SiteMode::cases())->mapWithKeys(fn (SiteMode $mode) => [$mode->value => str($mode->value)->headline()])),
            TextInput::make('default_locale')->required(), Select::make('status')->required()->options(SiteStatus::options())->default(SiteStatus::default()->value),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('name')->searchable(), TextColumn::make('market.name'), TextColumn::make('mode')->badge(), TextColumn::make('status')->badge()])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSites::route('/'), 'create' => Pages\CreateSite::route('/create'), 'edit' => Pages\EditSite::route('/{record}/edit'), 'products' => Pages\ManageSiteProducts::route('/{record}/products'), 'brands' => Pages\BrandVisibilityRules::route('/{record}/brands')];
    }

    public static function getRelations(): array
    {
        return [SiteFeaturesRelationManager::class];
    }
}
