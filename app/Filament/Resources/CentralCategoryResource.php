<?php

namespace App\Filament\Resources;

use App\Enums\CategorySchemaStatus;
use App\Enums\CentralCategoryStatus;
use App\Filament\Resources\CentralCategoryResource\Pages;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class CentralCategoryResource extends Resource
{
    protected static ?string $model = CentralCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Categories';

    protected static string|UnitEnum|null $navigationGroup = 'Central Catalog';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return self::canManageCategories();
    }

    public static function canView(Model $record): bool
    {
        return self::canManageCategories();
    }

    public static function canCreate(): bool
    {
        return self::canManageCategories();
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManageCategories();
    }

    private static function canManageCategories(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->can('catalog.categories.manage');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Parent category')
                    ->relationship(
                        'parent',
                        'name',
                        modifyQueryUsing: fn (Builder $query, ?CentralCategory $record): Builder => $record?->exists
                            ? $query->whereNotIn('id', $record->descendantIds())
                            : $query,
                        ignoreRecord: true,
                    )
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('status')
                    ->required()
                    ->options(CentralCategoryStatus::options())
                    ->default(CentralCategoryStatus::default()->value),
                Select::make('schema_status')
                    ->label('Schema status')
                    ->required()
                    ->options(CategorySchemaStatus::options())
                    ->default(CategorySchemaStatus::default()->value),
                TextInput::make('position')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (CentralCategoryStatus|string|null $state): string => CentralCategoryStatus::colorFor($state))
                    ->sortable(),
                TextColumn::make('schema_status')
                    ->label('Schema')
                    ->badge()
                    ->color(fn (CategorySchemaStatus|string|null $state): string => CategorySchemaStatus::colorFor($state))
                    ->sortable(),
                TextColumn::make('position')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('schema')
                    ->label('Schema')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->url(fn (CentralCategory $record): string => self::getUrl('schema', ['record' => $record])),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCentralCategories::route('/'),
            'create' => Pages\CreateCentralCategory::route('/create'),
            'edit' => Pages\EditCentralCategory::route('/{record}/edit'),
            'schema' => Pages\CategorySchemaBuilder::route('/{record}/schema'),
        ];
    }
}
