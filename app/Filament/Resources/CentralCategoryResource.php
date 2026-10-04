<?php

namespace App\Filament\Resources;

use App\Enums\CategorySchemaStatus;
use App\Enums\CentralCategoryStatus;
use App\Enums\Permission;
use App\Filament\Resources\CentralCategoryResource\Pages;
use App\Models\CentralCatalog\CentralCategory;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryHierarchy;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
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
        return self::canManageCategories(true);
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManageCategories(true);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    private static function canManageCategories(bool $mutation = false): bool
    {
        return app(CategoryAccess::class)->allows(Permission::CatalogCategoriesManage, $mutation);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Parent category')
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $set('new_hierarchy_revision', app(CategoryHierarchy::class)->revision($state === null || $state === '' ? null : (int) $state));
                    })
                    ->relationship(
                        'parent',
                        'name',
                        modifyQueryUsing: fn (Builder $query, ?CentralCategory $record): Builder => $record?->exists
                            ? $query->whereNotIn('id', $record->descendantIds())
                            : $query,
                        ignoreRecord: true,
                    )
                    ->saveRelationshipsUsing(null)
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Hidden::make('old_hierarchy_revision')->default(fn (): int => app(CategoryHierarchy::class)->revision(null)),
                Hidden::make('new_hierarchy_revision')->default(fn (): int => app(CategoryHierarchy::class)->revision(null)),
                TextInput::make('status')->disabled()->dehydrated(false)->default('draft'),
                TextInput::make('schema_status')->label('Schema status')->disabled()->dehydrated(false)->default('draft'),
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
                    ->visible(fn (): bool => app(CategoryAccess::class)->allows(Permission::CatalogSchemaManage))
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
