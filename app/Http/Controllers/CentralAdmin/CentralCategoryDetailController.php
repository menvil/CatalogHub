<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Enums\CentralCategoryStatus;
use App\Enums\Permission;
use App\Filament\Resources\CentralCategoryResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\CentralCategoryDetailRequest;
use App\Models\CentralCatalog\CentralCategory;
use App\Queries\CentralCatalog\CategoryDetailReadModelQuery;
use App\Services\Categories\CategoryAccess;
use Illuminate\Contracts\View\View;

final class CentralCategoryDetailController extends Controller
{
    public function __invoke(CentralCategoryDetailRequest $request, CentralCategory $category, CategoryDetailReadModelQuery $query, CategoryAccess $access): View
    {
        $detail = $query->forCategory($category, $request->localeId());
        $canMutate = $access->allows(Permission::CatalogCategoriesManage, true);
        $commands = ! $canMutate ? [] : match ($category->status) {
            CentralCategoryStatus::Draft => ['activate', 'archive'],
            CentralCategoryStatus::Active => ['archive'],
            CentralCategoryStatus::Archived => ['restore'],
        };

        return view('central-admin.categories.show', [
            'detail' => $detail,
            'editUrl' => $canMutate ? CentralCategoryResource::getUrl('edit', ['record' => $category]) : null,
            'schemaUrl' => $access->allows(Permission::CatalogSchemaManage) ? CentralCategoryResource::getUrl('schema', ['record' => $category]) : null,
            'translationUrl' => $detail->translations->localeId !== null && $access->allows(Permission::TranslationsManage)
                ? route('central.categories.translations.edit', [$category, $detail->translations->localeId]) : null,
            'commands' => $commands,
        ]);
    }
}
