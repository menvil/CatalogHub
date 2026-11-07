<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\CreateCentralCategoryAction;
use App\Actions\CentralCatalog\SaveCentralCategoryAction;
use App\Enums\Permission;
use App\Filament\Resources\CentralCategoryResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\CentralCategoryEditRequest;
use App\Http\Requests\CentralAdmin\CentralCategoryStoreRequest;
use App\Http\Requests\CentralAdmin\CentralCategoryUpdateRequest;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Queries\CentralCatalog\CategoryFormReadModelQuery;
use App\Queries\CentralCatalog\CategorySlugImpactQuery;
use App\Services\Categories\CategoryAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/** CA-018: the one canonical Category Create/Edit experience. */
final class CentralCategoryFormController extends Controller
{
    public function create(CategoryFormReadModelQuery $query): View
    {
        return view('central-admin.categories.create', ['form' => $query->forCreate()]);
    }

    public function store(CentralCategoryStoreRequest $request, CreateCentralCategoryAction $create): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $category = $create->handle($actor, $request->categoryData(), $request->scopeRevision());

        return redirect()->route('central.categories.show', $category)
            ->with('success', 'Category #'.$category->id.' created as Draft.');
    }

    public function edit(CentralCategoryEditRequest $request, CentralCategory $category, CategoryFormReadModelQuery $query, CategorySlugImpactQuery $impacts, CategoryAccess $access): View
    {
        $form = $query->forEdit($category);
        $proposed = $request->slugPreview();

        return view('central-admin.categories.edit', [
            'form' => $form,
            'slugImpact' => $proposed !== null && $proposed !== $category->slug ? $impacts->assess($category, $category->slug, $proposed) : null,
            'schemaUrl' => $access->allows(Permission::CatalogSchemaManage) ? CentralCategoryResource::getUrl('schema', ['record' => $category]) : null,
            'translationUrl' => $form->translationLocaleId !== null && $access->allows(Permission::TranslationsManage)
                ? route('central.categories.translations.edit', [$category, $form->translationLocaleId]) : null,
        ]);
    }

    public function update(CentralCategoryUpdateRequest $request, CentralCategory $category, SaveCentralCategoryAction $save): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $before = [$category->name, $category->slug, $category->parent_id, $category->position];
        try {
            $saved = $save->handle($actor, $category, $request->saveData());
        } catch (ValidationException $exception) {
            throw $exception->redirectTo($request->editUrl());
        }
        $after = [$saved->name, $saved->slug, $saved->parent_id, $saved->position];

        return redirect()->route('central.categories.show', $saved)
            ->with('success', $before === $after ? 'No changes to save.' : 'Category updated.');
    }
}
