<?php

namespace App\Filament\Resources;

use App\Enums\MarketStatus;
use App\Filament\Resources\MarketResource\Pages;
use App\Models\Market;
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

final class MarketResource extends Resource
{
    protected static ?string $model = Market::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Portal Admin';

    public static function canViewAny(): bool
    {
        return self::canManageMarkets();
    }

    public static function canView(Model $record): bool
    {
        return self::canManageMarkets();
    }

    public static function canCreate(): bool
    {
        return self::canManageMarkets();
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManageMarkets();
    }

    private static function canManageMarkets(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->can('central.manage');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(32)->unique(ignoreRecord: true),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('country_code')->required()->maxLength(2),
            TextInput::make('currency_code')->required()->maxLength(3),
            TextInput::make('default_locale')->required()->maxLength(32),
            TextInput::make('timezone')->required()->maxLength(64),
            Select::make('status')->required()->options(MarketStatus::options())->default(MarketStatus::default()->value),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable()->sortable(), TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('currency_code'), TextColumn::make('default_locale'),
            TextColumn::make('status')->badge()->color(fn (MarketStatus|string|null $state): string => MarketStatus::colorFor($state)),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMarkets::route('/'), 'create' => Pages\CreateMarket::route('/create'), 'edit' => Pages\EditMarket::route('/{record}/edit')];
    }
}
