<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarketUnitPreferenceResource\Pages;
use App\Models\MarketUnitPreference;
use App\Models\MeasurementUnit;
use App\Services\Units\UnitConverter;
use App\Services\Units\UnitFormatter;
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

final class MarketUnitPreferenceResource extends Resource
{
    protected static ?string $model = MarketUnitPreference::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Market Unit Preferences';

    protected static string|UnitEnum|null $navigationGroup = 'Units';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('market_code')
                    ->required()
                    ->maxLength(16),
                Select::make('dimension_id')
                    ->relationship('dimension', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('preferred_unit_id')
                    ->relationship('preferredUnit', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('market_code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dimension.code')
                    ->label('Dimension')
                    ->sortable(),
                TextColumn::make('preferredUnit.code')
                    ->label('Preferred Unit')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function previewCanonicalValue(float|string $canonicalValue, MeasurementUnit $displayUnit): string
    {
        $displayValue = app(UnitConverter::class)->fromCanonical($canonicalValue, $displayUnit);

        return app(UnitFormatter::class)->format($displayValue, $displayUnit);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isCentralAdmin() || $user->isSuperAdmin());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketUnitPreferences::route('/'),
            'create' => Pages\CreateMarketUnitPreference::route('/create'),
            'edit' => Pages\EditMarketUnitPreference::route('/{record}/edit'),
        ];
    }
}
