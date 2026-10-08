<?php

namespace App\Filament\Resources;

use App\Enums\Permission;
use App\Filament\Resources\CentralCategoryResource\Pages;
use App\Models\CentralCatalog\CentralCategory;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryHierarchy;
use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class CentralCategoryResource extends Resource
{
    protected static ?string $model = CentralCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Categories';

    protected static string|UnitEnum|null $navigationGroup = 'Central Catalog';

    protected static bool $shouldRegisterNavigation = false;

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

    /** @param array<mixed> $parameters */
    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false, ?string $configuration = null): string
    {
        if ($name === null || $name === 'index') {
            return route('central.categories.index', $parameters, $isAbsolute);
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);
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
