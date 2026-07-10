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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
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
                    ->maxLength(16)
                    ->unique(
                        table: 'market_unit_preferences',
                        column: 'market_code',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                            ->where('dimension_id', $get('dimension_id')),
                    ),
                Select::make('dimension_id')
                    ->relationship('dimension', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set): mixed => $set('preferred_unit_id', null)),
                Select::make('preferred_unit_id')
                    ->relationship(
                        'preferredUnit',
                        'name',
                        modifyQueryUsing: fn ($query, Get $get) => $query->when(
                            $get('dimension_id'),
                            fn ($query, mixed $dimensionId) => $query->where('dimension_id', $dimensionId),
                        ),
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->rules([
                        fn (Get $get) => Rule::exists('measurement_units', 'id')
                            ->where('dimension_id', $get('dimension_id')),
                    ]),
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
