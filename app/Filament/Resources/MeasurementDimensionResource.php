<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeasurementDimensionResource\Pages;
use App\Models\MeasurementDimension;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class MeasurementDimensionResource extends Resource
{
    protected static ?string $model = MeasurementDimension::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Measurement Dimensions';

    protected static string|UnitEnum|null $navigationGroup = 'Units';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('base_unit_code')
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('base_unit_code')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('units_count')
                    ->counts('units')
                    ->label('Units'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isCentralAdmin() || $user->isSuperAdmin());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeasurementDimensions::route('/'),
            'create' => Pages\CreateMeasurementDimension::route('/create'),
            'edit' => Pages\EditMeasurementDimension::route('/{record}/edit'),
        ];
    }
}
