<?php

namespace App\Filament\Resources;

use App\Actions\CentralCatalog\ArchiveCentralProductAction;
use App\Actions\CentralCatalog\RestoreCentralProductAction;
use App\Enums\CentralProductStatus;
use App\Filament\Resources\CentralProductResource\Pages;
use App\Models\CentralCatalog\CentralProduct;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class CentralProductResource extends Resource
{
    protected static ?string $model = CentralProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Products';

    protected static string|UnitEnum|null $navigationGroup = 'Central Catalog';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('central_brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('central_category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('model')
                    ->maxLength(255),
                TextInput::make('slug')
                    ->nullable()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('status')
                    ->required()
                    ->options(CentralProductStatus::options())
                    ->default(CentralProductStatus::default()->value),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('model')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (CentralProductStatus|string|null $state): string => CentralProductStatus::colorFor($state))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('archive')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (CentralProduct $record): bool => $record->status !== CentralProductStatus::Archived)
                    ->action(fn (CentralProduct $record): CentralProduct => app(ArchiveCentralProductAction::class)->handle($record)),
                Action::make('restore')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (CentralProduct $record): bool => $record->status === CentralProductStatus::Archived)
                    ->action(fn (CentralProduct $record): CentralProduct => app(RestoreCentralProductAction::class)->handle($record)),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('model')
                    ->placeholder('None'),
                TextEntry::make('slug'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (CentralProductStatus|string|null $state): string => CentralProductStatus::colorFor($state)),
                TextEntry::make('brand.name')
                    ->label('Brand')
                    ->placeholder('None'),
                TextEntry::make('category.name')
                    ->label('Category')
                    ->placeholder('None'),
                TextEntry::make('variants_count')
                    ->label('Variants')
                    ->state(fn (CentralProduct $record): int => (int) ($record->variants_count ?? $record->variants()->count())),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCentralProducts::route('/'),
            'create' => Pages\CreateCentralProduct::route('/create'),
            'view' => Pages\ViewCentralProduct::route('/{record}'),
            'edit' => Pages\EditCentralProduct::route('/{record}/edit'),
        ];
    }

}
