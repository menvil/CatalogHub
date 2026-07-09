<?php

namespace App\Filament\Resources;

use App\Enums\CentralBrandStatus;
use App\Filament\Resources\CentralBrandResource\Pages;
use App\Models\CentralCatalog\CentralBrand;
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

final class CentralBrandResource extends Resource
{
    protected static ?string $model = CentralBrand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Brands';

    protected static string|UnitEnum|null $navigationGroup = 'Central Catalog';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('status')
                    ->required()
                    ->options(CentralBrandStatus::options())
                    ->default(CentralBrandStatus::default()->value),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (CentralBrandStatus|string|null $state): string => CentralBrandStatus::colorFor($state))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCentralBrands::route('/'),
            'create' => Pages\CreateCentralBrand::route('/create'),
            'edit' => Pages\EditCentralBrand::route('/{record}/edit'),
        ];
    }

}
