<?php

namespace App\Filament\Resources;

use App\Enums\Permission;
use App\Filament\Resources\CentralCategoryResource\Pages;
use App\Models\CentralCatalog\CentralCategory;
use App\Services\Categories\CategoryAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
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

    /** @param array<mixed> $parameters */
    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false, ?string $configuration = null): string
    {
        if ($name === null || $name === 'index') {
            return route('central.categories.index', $parameters, $isAbsolute);
        }
        // CA-018 owns Create/Edit; generated legacy URLs target the canonical routes directly (no redirect loop).
        if ($name === 'create') {
            return route('central.categories.create', [], $isAbsolute);
        }
        if ($name === 'edit') {
            $record = $parameters['record'] ?? null;

            return route('central.categories.edit', ['category' => $record instanceof Model ? $record->getKey() : $record], $isAbsolute);
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
