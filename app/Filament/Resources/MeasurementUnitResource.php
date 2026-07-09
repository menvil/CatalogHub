<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeasurementUnitResource\Pages;
use App\Models\MeasurementUnit;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class MeasurementUnitResource extends Resource
{
    protected static ?string $model = MeasurementUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $navigationLabel = 'Measurement Units';

    protected static string|UnitEnum|null $navigationGroup = 'Units';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('dimension_id')
                    ->relationship('dimension', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('symbol')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('system')
                    ->required()
                    ->options([
                        'metric' => 'Metric',
                        'imperial' => 'Imperial',
                    ])
                    ->default('metric'),
                TextInput::make('factor_to_canonical')
                    ->numeric()
                    ->rules(['not_in:0'])
                    ->required()
                    ->default(1),
                TextInput::make('offset_to_canonical')
                    ->numeric()
                    ->required()
                    ->default(0),
                TextInput::make('precision_default')
                    ->integer()
                    ->minValue(0)
                    ->maxValue(255)
                    ->required()
                    ->default(2),
                TagsInput::make('aliases_json')
                    ->label('Aliases')
                    ->columnSpanFull(),
                Toggle::make('is_canonical')
                    ->default(false),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dimension.code')
                    ->label('Dimension')
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('symbol')
                    ->sortable(),
                TextColumn::make('system')
                    ->sortable(),
                TextColumn::make('factor_to_canonical')
                    ->label('Factor'),
                TextColumn::make('offset_to_canonical')
                    ->label('Offset'),
                TextColumn::make('is_canonical')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Canonical' : 'Derived'),
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
            'index' => Pages\ListMeasurementUnits::route('/'),
            'create' => Pages\CreateMeasurementUnit::route('/create'),
            'edit' => Pages\EditMeasurementUnit::route('/{record}/edit'),
        ];
    }
}
